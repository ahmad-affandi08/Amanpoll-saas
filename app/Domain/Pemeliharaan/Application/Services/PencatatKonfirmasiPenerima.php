<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Application\Services;

use App\Core\Audit\LayananAudit;
use App\Core\Izin\ScopeLingkup;
use App\Domain\Kolaborasi\Application\Services\PenyimpanBerkas;
use App\Domain\Pemeliharaan\Application\Actions\UbahStatusPerintahKerja;
use App\Domain\Pemeliharaan\Domain\Enums\HasilKonfirmasiPenerima;
use App\Domain\Pemeliharaan\Domain\Enums\MetodeKonfirmasiPenerima;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KonfirmasiPenerimaPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\RiwayatStatusPerintahKerja;
use App\Domain\Platform\Application\Actions\SimpanTandaTanganPengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Http\UploadedFile;

/**
 * Inti pencatatan konfirmasi penerima, dipakai ketiga Action cara konfirmasi (PRD 8.22).
 *
 * Di bawah kunci baris perintah kerja:
 * - kiriman ulang berkunci perangkat yang sama mengembalikan catatan lama (antrean offline);
 * - status perintah kerja harus termasuk yang diizinkan cara itu;
 * - hanya satu "Diterima" berlaku per siklus penyelesaian;
 * - "Diterima" selalu membawa tanda tangan: gambar yang dikirim (disimpan ke profil
 *   penerima bila ia mengonfirmasi dari akunnya, "gambar sekali lalu tersimpan"), atau
 *   tanda tangan tersimpan penerima saat itu yang dicap dengan merujuk berkasnya;
 * - "Masih bermasalah" wajib beralasan dan mengembalikan perintah kerja ke Dikerjakan
 *   lewat `UbahStatusPerintahKerja`, lalu teknisi diberi tahu.
 *
 * Setiap jawaban tercatat di riwayat status perintah kerja dan audit.
 */
final class PencatatKonfirmasiPenerima
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananAudit $audit,
        private readonly AturanKonfirmasiPenerima $aturan,
        private readonly UbahStatusPerintahKerja $ubahStatus,
        private readonly SimpanTandaTanganPengguna $simpanTandaTangan,
        private readonly PenyimpanBerkas $penyimpanBerkas,
        private readonly PemberiTahuKonfirmasiPenerima $pemberiTahu,
    ) {}

    /**
     * @param  list<StatusPerintahKerja>  $statusDiizinkan
     * @param  array{NamaPenerima?: string|null, JabatanPenerima?: string|null, Alasan?: string|null, Ulasan?: string|null, Penilaian?: int|null, KunciPerangkat?: string|null}  $data
     * @param  Pengguna|null  $penerima  Akun penerima yang mengonfirmasi sendiri; `null` untuk tanda tangan di HP teknisi.
     */
    public function catat(
        PerintahKerja $perintahKerja,
        MetodeKonfirmasiPenerima $metode,
        HasilKonfirmasiPenerima $hasil,
        array $statusDiizinkan,
        array $data,
        ?Pengguna $penerima,
        string $dicatatOleh,
        ?UploadedFile $gambar,
    ): KonfirmasiPenerimaPerintahKerja {
        $namaPenerima = trim((string) ($penerima instanceof Pengguna ? $penerima->Nama : ($data['NamaPenerima'] ?? '')));
        $alasan = trim((string) ($data['Alasan'] ?? ''));

        if ($namaPenerima === '') {
            throw new AturanBisnisDilanggar('Nama penerima wajib diisi.');
        }
        if ($hasil === HasilKonfirmasiPenerima::MasihBermasalah && $alasan === '') {
            throw new AturanBisnisDilanggar('Ceritakan apa yang masih bermasalah.');
        }
        $penilaian = $data['Penilaian'] ?? null;
        if ($penilaian !== null && ($penilaian < 1 || $penilaian > 5)) {
            throw new AturanBisnisDilanggar('Penilaian perbaikan 1 sampai 5 bintang.');
        }

        $catatan = $this->transaksi->jalankan(function () use ($perintahKerja, $metode, $hasil, $statusDiizinkan, $data, $penerima, $dicatatOleh, $gambar, $namaPenerima, $alasan, $penilaian): KonfirmasiPenerimaPerintahKerja {
            $terkunci = PerintahKerja::query()->withoutGlobalScope(ScopeLingkup::class)->lockForUpdate()->findOrFail($perintahKerja->Id);
            $kunciPerangkat = filled($data['KunciPerangkat'] ?? null) ? (string) $data['KunciPerangkat'] : null;

            if ($kunciPerangkat !== null) {
                $lama = KonfirmasiPenerimaPerintahKerja::query()->where('KunciPerangkat', $kunciPerangkat)->first();
                if ($lama instanceof KonfirmasiPenerimaPerintahKerja) {
                    if ($lama->PerintahKerjaId !== $terkunci->Id) {
                        throw new AturanBisnisDilanggar('Kunci perangkat sudah dipakai untuk pekerjaan lain.');
                    }

                    return $lama;
                }
            }

            $status = StatusPerintahKerja::from($terkunci->Status);
            if (! in_array($status, $statusDiizinkan, true)) {
                throw new AturanBisnisDilanggar('Pekerjaan ini tidak sedang menunggu konfirmasi penerima.');
            }

            $sudah = $this->aturan->berlaku($terkunci);
            if ($sudah instanceof KonfirmasiPenerimaPerintahKerja) {
                throw new AturanBisnisDilanggar("Pekerjaan ini sudah dikonfirmasi {$sudah->NamaPenerima}.");
            }

            $sekarang = now();
            $dasar = [
                'PerintahKerjaId' => $terkunci->Id,
                'Metode' => $metode->value,
                'Hasil' => $hasil->value,
                'PenggunaId' => $penerima?->Id,
                'DicatatOleh' => $dicatatOleh,
                'NamaPenerima' => mb_substr($namaPenerima, 0, 150),
                'JabatanPenerima' => $this->jabatan($penerima, $data),
                'KunciPerangkat' => $kunciPerangkat,
                'DikonfirmasiPada' => $sekarang,
            ];

            if ($hasil === HasilKonfirmasiPenerima::MasihBermasalah) {
                $this->ubahStatus->jalankan(
                    $terkunci,
                    StatusPerintahKerja::Dikerjakan,
                    "Penerima {$namaPenerima}: masih bermasalah. {$alasan}",
                    null,
                    $terkunci->Versi,
                    $dicatatOleh,
                );
                $catatan = KonfirmasiPenerimaPerintahKerja::create([...$dasar, 'Alasan' => $alasan, 'Berlaku' => false]);
            } else {
                $catatan = KonfirmasiPenerimaPerintahKerja::create([
                    ...$dasar,
                    'TandaTanganBerkasId' => $this->tandaTangan($penerima, $gambar, $dicatatOleh),
                    'Ulasan' => filled($data['Ulasan'] ?? null) ? (string) $data['Ulasan'] : null,
                    'Penilaian' => $penilaian,
                    'Berlaku' => true,
                ]);
                RiwayatStatusPerintahKerja::create([
                    'PerintahKerjaId' => $terkunci->Id,
                    'StatusSebelum' => $terkunci->Status,
                    'StatusSesudah' => $terkunci->Status,
                    'Catatan' => "Diterima {$namaPenerima} ({$metode->label()}).",
                    'DiubahOleh' => $dicatatOleh,
                    'DiubahPada' => $sekarang,
                ]);
            }

            $this->audit->catat('KonfirmasiPenerima', 'PerintahKerja', $terkunci->Id, dataSesudah: [
                'KonfirmasiPenerimaId' => $catatan->Id,
                'Metode' => $metode->value,
                'Hasil' => $hasil->value,
                'PenggunaId' => $penerima?->Id,
                'NamaPenerima' => $catatan->NamaPenerima,
                'TandaTanganBerkasId' => $catatan->TandaTanganBerkasId,
            ]);

            return $catatan;
        });

        if ($catatan->wasRecentlyCreated) {
            $perintahKerjaTerkini = PerintahKerja::query()->withoutGlobalScope(ScopeLingkup::class)->findOrFail($perintahKerja->Id);
            $hasil === HasilKonfirmasiPenerima::MasihBermasalah
                ? $this->pemberiTahu->masihBermasalah($perintahKerjaTerkini, $namaPenerima, $alasan)
                : $this->pemberiTahu->diterima($perintahKerjaTerkini, $namaPenerima);
        }

        return $catatan;
    }

    /**
     * Berkas tanda tangan yang dicap.
     *
     * Penerima yang mengonfirmasi dari akunnya: gambar baru disimpan ke profilnya lebih
     * dulu, lalu berkas itu yang dicap; tanpa gambar, tanda tangan tersimpannya yang
     * dicap. Tidak ada jalur memakai tanda tangan orang lain. Tanpa akun (HP teknisi):
     * gambar wajib, disimpan sebagai berkas biasa atas nama teknisi.
     */
    private function tandaTangan(?Pengguna $penerima, ?UploadedFile $gambar, string $dicatatOleh): string
    {
        if ($penerima instanceof Pengguna) {
            if ($gambar instanceof UploadedFile) {
                return $this->simpanTandaTangan->jalankan($penerima, $gambar)->Id;
            }

            $tersimpan = Pengguna::query()->whereKey($penerima->Id)->value('TandaTanganBerkasId');
            if (! is_string($tersimpan) || $tersimpan === '') {
                throw new AturanBisnisDilanggar('Gambar tanda tanganmu dulu. Setelah itu tersimpan dan konfirmasi berikutnya cukup satu ketukan.');
            }

            return $tersimpan;
        }

        if (! $gambar instanceof UploadedFile) {
            throw new AturanBisnisDilanggar('Tanda tangan penerima wajib digambar.');
        }

        return $this->penyimpanBerkas->simpanUnggahan($gambar, $dicatatOleh, ['Keperluan' => 'TandaTanganPenerima'])->Id;
    }

    /** @param  array{JabatanPenerima?: string|null}  $data */
    private function jabatan(?Pengguna $penerima, array $data): ?string
    {
        $jabatan = trim((string) ($penerima instanceof Pengguna ? $penerima->Jabatan : ($data['JabatanPenerima'] ?? '')));

        return $jabatan === '' ? null : mb_substr($jabatan, 0, 150);
    }
}
