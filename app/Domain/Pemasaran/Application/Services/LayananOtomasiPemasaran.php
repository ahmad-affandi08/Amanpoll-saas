<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Core\Audit\LayananAudit;
use App\Domain\Pemasaran\Domain\Enums\JenisLangkahOtomasi;
use App\Domain\Pemasaran\Domain\Enums\OperatorKondisi;
use App\Domain\Pemasaran\Domain\Enums\StatusOtomasi;
use App\Domain\Pemasaran\Domain\KatalogKondisiOtomasi;
use App\Domain\Pemasaran\Domain\KatalogPemicuOtomasi;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\LangkahOtomasiPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\OtomasiPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\VersiOtomasiPemasaran;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Validator;

/** Menyusun otomasi dari konsol; langkah divalidasi saat disimpan, bukan saat peristiwanya sudah lewat (MARKETING.md 17). */
final class LayananOtomasiPemasaran
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly RegistriTindakanOtomasi $tindakan,
        private readonly LayananAudit $audit,
    ) {}

    /** @param array{Kode: string, Nama: string, Keterangan?: string|null, Pemicu: string} $data */
    public function simpan(?OtomasiPemasaran $otomasi, array $data): OtomasiPemasaran
    {
        if (! KatalogPemicuOtomasi::dikenal($data['Pemicu'])) {
            throw new AturanBisnisDilanggar("Pemicu {$data['Pemicu']} tidak dikenal.");
        }

        return $this->transaksi->jalankan(function () use ($otomasi, $data): OtomasiPemasaran {
            $sebelum = $otomasi?->only(['Kode', 'Nama', 'Pemicu', 'Aktif']);

            if ($otomasi === null) {
                $otomasi = OtomasiPemasaran::create([...$data, 'Aktif' => false]);
                $this->buatVersi($otomasi);
            } else {
                $otomasi->fill($data);
                $otomasi->save();
            }

            $this->audit->catat(
                $sebelum === null ? 'OtomasiPemasaran.Dibuat' : 'OtomasiPemasaran.Diubah',
                'OtomasiPemasaran',
                $otomasi->Id,
                dataSebelum: $sebelum,
                dataSesudah: $otomasi->only(['Kode', 'Nama', 'Pemicu', 'Aktif']),
            );

            return $otomasi;
        });
    }

    /** Draf baru selalu lahir dari nol atau dari salinan versi aktifnya, tidak pernah menimpa yang berjalan. */
    public function buatVersi(OtomasiPemasaran $otomasi, bool $salinVersiAktif = false): VersiOtomasiPemasaran
    {
        return $this->transaksi->jalankan(function () use ($otomasi, $salinVersiAktif): VersiOtomasiPemasaran {
            $draf = $this->drafBerjalan($otomasi);

            if ($draf !== null) {
                return $draf;
            }

            $nomor = (int) VersiOtomasiPemasaran::query()
                ->where('OtomasiPemasaranId', $otomasi->Id)
                ->max('Nomor');

            $versi = VersiOtomasiPemasaran::create([
                'OtomasiPemasaranId' => $otomasi->Id,
                'Nomor' => $nomor + 1,
                'Status' => StatusOtomasi::Draf,
            ]);

            if ($salinVersiAktif && $otomasi->VersiAktifId !== null) {
                $this->salinLangkah($otomasi->VersiAktifId, $versi);
            }

            return $versi;
        });
    }

    /** @param array{Jenis: string, Urutan: int, Konfigurasi: array<string, mixed>} $data */
    public function simpanLangkah(
        VersiOtomasiPemasaran $versi,
        ?LangkahOtomasiPemasaran $langkah,
        array $data,
    ): LangkahOtomasiPemasaran {
        $this->pastikanDraf($versi);

        $jenis = JenisLangkahOtomasi::tryFrom($data['Jenis']);

        if ($jenis === null) {
            throw new AturanBisnisDilanggar("Jenis langkah {$data['Jenis']} tidak dikenal.");
        }

        $konfigurasi = $this->periksaKonfigurasi($jenis, $data['Konfigurasi']);

        return $this->transaksi->jalankan(function () use ($versi, $langkah, $data, $jenis, $konfigurasi): LangkahOtomasiPemasaran {
            if ($langkah === null) {
                return LangkahOtomasiPemasaran::create([
                    'VersiOtomasiPemasaranId' => $versi->Id,
                    'Urutan' => $data['Urutan'],
                    'Jenis' => $jenis->value,
                    'Konfigurasi' => $konfigurasi,
                    'DibuatPada' => CarbonImmutable::now(),
                ]);
            }

            $langkah->Urutan = max($data['Urutan'], 0);
            $langkah->Jenis = $jenis;
            $langkah->Konfigurasi = $konfigurasi;
            $langkah->save();

            return $langkah;
        });
    }

    public function hapusLangkah(VersiOtomasiPemasaran $versi, LangkahOtomasiPemasaran $langkah): void
    {
        $this->pastikanDraf($versi);
        $langkah->delete();
    }

    /** Mengaktifkan versi berarti mulai mengirim pesan ke orang sungguhan, jadi dicatat dan divalidasi ulang. */
    public function aktifkan(VersiOtomasiPemasaran $versi, ?string $olehId = null): VersiOtomasiPemasaran
    {
        $otomasi = $versi->otomasi;

        if ($otomasi === null) {
            throw new AturanBisnisDilanggar('Versi ini tidak tertaut ke otomasi mana pun.');
        }

        if (! $versi->Status->bolehPindahKe(StatusOtomasi::Aktif)) {
            throw new AturanBisnisDilanggar(
                "Versi berstatus {$versi->Status->value} tidak dapat diaktifkan.",
            );
        }

        $langkah = $versi->langkah()->get();

        if ($langkah->isEmpty()) {
            throw new AturanBisnisDilanggar('Versi tanpa langkah tidak dapat diaktifkan.');
        }

        foreach ($langkah as $satu) {
            $this->periksaKonfigurasi($satu->Jenis, (array) $satu->Konfigurasi);
        }

        return $this->transaksi->jalankan(function () use ($otomasi, $versi, $olehId): VersiOtomasiPemasaran {
            VersiOtomasiPemasaran::query()
                ->where('OtomasiPemasaranId', $otomasi->Id)
                ->where('Id', '!=', $versi->Id)
                ->where('Status', StatusOtomasi::Aktif->value)
                ->update(['Status' => StatusOtomasi::Diarsipkan->value]);

            $versi->Status = StatusOtomasi::Aktif;
            $versi->DiterbitkanOlehId = $olehId;
            $versi->DiterbitkanPada = CarbonImmutable::now();
            $versi->save();

            $otomasi->VersiAktifId = $versi->Id;
            $otomasi->save();

            $this->audit->catat('OtomasiPemasaran.VersiDiaktifkan', 'VersiOtomasiPemasaran', $versi->Id, dataSesudah: [
                'OtomasiKode' => $otomasi->Kode,
                'Nomor' => $versi->Nomor,
            ]);

            return $versi;
        });
    }

    public function ubahAktif(OtomasiPemasaran $otomasi, bool $aktif): OtomasiPemasaran
    {
        if ($aktif && $otomasi->VersiAktifId === null) {
            throw new AturanBisnisDilanggar('Otomasi tanpa versi aktif tidak dapat dinyalakan.');
        }

        $otomasi->Aktif = $aktif;
        $otomasi->save();

        $this->audit->catat(
            $aktif ? 'OtomasiPemasaran.Dinyalakan' : 'OtomasiPemasaran.Dimatikan',
            'OtomasiPemasaran',
            $otomasi->Id,
            dataSesudah: ['Kode' => $otomasi->Kode, 'Aktif' => $aktif],
        );

        return $otomasi;
    }

    /**
     * @param  array<string, mixed>  $konfigurasi
     * @return array<string, mixed>
     */
    public function periksaKonfigurasi(JenisLangkahOtomasi $jenis, array $konfigurasi): array
    {
        return match ($jenis) {
            JenisLangkahOtomasi::Kondisi => $this->periksaKondisi($konfigurasi),
            JenisLangkahOtomasi::Jeda => $this->periksaJeda($konfigurasi),
            JenisLangkahOtomasi::Aksi => $this->periksaAksi($konfigurasi),
        };
    }

    /**
     * @param  array<string, mixed>  $konfigurasi
     * @return array<string, mixed>
     */
    private function periksaKondisi(array $konfigurasi): array
    {
        $daftar = array_values((array) ($konfigurasi['Kondisi'] ?? []));

        if ($daftar === []) {
            throw new AturanBisnisDilanggar('Langkah kondisi memerlukan setidaknya satu kondisi.');
        }

        foreach ($daftar as $satu) {
            $bidang = (string) ($satu['Bidang'] ?? '');
            $operator = OperatorKondisi::tryFrom((string) ($satu['Operator'] ?? ''));

            if (! KatalogKondisiOtomasi::dikenal($bidang)) {
                throw new AturanBisnisDilanggar("Bidang kondisi {$bidang} tidak dikenal.");
            }

            if ($operator === null || ! in_array($operator->value, KatalogKondisiOtomasi::operator($bidang), true)) {
                throw new AturanBisnisDilanggar("Operator tidak berlaku untuk bidang {$bidang}.");
            }

            if (! $operator->tanpaNilai() && ($satu['Nilai'] ?? null) === null) {
                throw new AturanBisnisDilanggar("Kondisi {$bidang} memerlukan nilai pembanding.");
            }
        }

        return ['Kondisi' => $daftar];
    }

    /**
     * @param  array<string, mixed>  $konfigurasi
     * @return array<string, mixed>
     */
    private function periksaJeda(array $konfigurasi): array
    {
        $menit = (int) ($konfigurasi['Menit'] ?? 0);

        if ($menit < 1 || $menit > 525600) {
            throw new AturanBisnisDilanggar('Jeda harus antara satu menit dan satu tahun.');
        }

        return ['Menit' => $menit];
    }

    /**
     * @param  array<string, mixed>  $konfigurasi
     * @return array<string, mixed>
     */
    private function periksaAksi(array $konfigurasi): array
    {
        $kode = (string) ($konfigurasi['Aksi'] ?? '');
        $tindakan = $this->tindakan->ambil($kode);
        $isi = (array) ($konfigurasi['Konfigurasi'] ?? []);

        $pemeriksa = Validator::make($isi, $tindakan->aturan());

        if ($pemeriksa->fails()) {
            throw new AturanBisnisDilanggar(
                "Konfigurasi aksi {$kode} tidak sah: ".implode(' ', $pemeriksa->errors()->all()),
            );
        }

        return ['Aksi' => $kode, 'Konfigurasi' => $isi];
    }

    private function drafBerjalan(OtomasiPemasaran $otomasi): ?VersiOtomasiPemasaran
    {
        return VersiOtomasiPemasaran::query()
            ->where('OtomasiPemasaranId', $otomasi->Id)
            ->where('Status', StatusOtomasi::Draf->value)
            ->orderByDesc('Nomor')
            ->first();
    }

    private function salinLangkah(string $dariVersiId, VersiOtomasiPemasaran $ke): void
    {
        $asal = LangkahOtomasiPemasaran::query()
            ->where('VersiOtomasiPemasaranId', $dariVersiId)
            ->orderBy('Urutan')
            ->get();

        foreach ($asal as $satu) {
            LangkahOtomasiPemasaran::create([
                'VersiOtomasiPemasaranId' => $ke->Id,
                'Urutan' => $satu->Urutan,
                'Jenis' => $satu->Jenis->value,
                'Konfigurasi' => $satu->Konfigurasi,
                'DibuatPada' => CarbonImmutable::now(),
            ]);
        }
    }

    private function pastikanDraf(VersiOtomasiPemasaran $versi): void
    {
        if ($versi->Status !== StatusOtomasi::Draf) {
            throw new AturanBisnisDilanggar(
                'Hanya versi draf yang dapat disunting; eksekusi yang berjalan memakai versi yang sudah dikunci.',
            );
        }
    }
}
