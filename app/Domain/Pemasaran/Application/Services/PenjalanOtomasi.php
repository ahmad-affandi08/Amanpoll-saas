<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Domain\Pemasaran\Domain\Enums\HasilLangkahOtomasi;
use App\Domain\Pemasaran\Domain\Enums\JenisLangkahOtomasi;
use App\Domain\Pemasaran\Domain\Enums\StatusEksekusiOtomasi;
use App\Domain\Pemasaran\Domain\KatalogKonfigurasiPemasaran;
use App\Domain\Pemasaran\Domain\ValueObjects\KonteksOtomasi;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\EksekusiOtomasiPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\LangkahOtomasiPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\LogEksekusiOtomasi;
use Carbon\CarbonImmutable;
use Throwable;

/** Menjalankan eksekusi dari langkah terakhirnya; langkah yang sudah punya log sukses dilewati (MARKETING.md 17). */
final class PenjalanOtomasi
{
    public function __construct(
        private readonly PengevaluasiKondisiOtomasi $kondisi,
        private readonly RegistriTindakanOtomasi $tindakan,
        private readonly PenyusunKonteksOtomasi $penyusun,
        private readonly LayananKonfigurasiPemasaran $konfigurasi,
    ) {}

    public function jalankan(EksekusiOtomasiPemasaran $eksekusi): StatusEksekusiOtomasi
    {
        if ($eksekusi->Status->final()) {
            return $eksekusi->Status;
        }

        $event = $eksekusi->event;
        $konteks = $event === null
            ? new KonteksOtomasi(null, $eksekusi->OrganisasiId, null)
            : $this->penyusun->dariEvent($event);

        $langkah = $this->langkah($eksekusi);
        $sudah = $this->urutanSukses($eksekusi);

        foreach ($langkah as $satu) {
            if ($satu->Urutan < $eksekusi->LangkahBerikutnya) {
                continue;
            }

            if (in_array($satu->Urutan, $sudah, true)) {
                $eksekusi->LangkahBerikutnya = $satu->Urutan + 1;

                continue;
            }

            $lanjut = $this->satuLangkah($eksekusi, $satu, $konteks);

            if (! $lanjut) {
                return $eksekusi->Status;
            }
        }

        return $this->tandai($eksekusi, StatusEksekusiOtomasi::Selesai);
    }

    /** Kembalian false berarti eksekusi berhenti di sini: tertunda, gagal, atau kondisi tidak terpenuhi. */
    private function satuLangkah(
        EksekusiOtomasiPemasaran $eksekusi,
        LangkahOtomasiPemasaran $langkah,
        KonteksOtomasi $konteks,
    ): bool {
        /** @var array<string, mixed> $konfigurasi */
        $konfigurasi = $langkah->Konfigurasi;

        try {
            return match ($langkah->Jenis) {
                JenisLangkahOtomasi::Kondisi => $this->jalankanKondisi($eksekusi, $langkah, $konteks, $konfigurasi),
                JenisLangkahOtomasi::Jeda => $this->jalankanJeda($eksekusi, $langkah, $konfigurasi),
                JenisLangkahOtomasi::Aksi => $this->jalankanAksi($eksekusi, $langkah, $konteks, $konfigurasi),
            };
        } catch (Throwable $galat) {
            $this->catat($eksekusi, $langkah, HasilLangkahOtomasi::Gagal, $galat->getMessage());
            $this->gagalkan($eksekusi, $galat->getMessage());

            return false;
        }
    }

    /** @param array<string, mixed> $konfigurasi */
    private function jalankanKondisi(
        EksekusiOtomasiPemasaran $eksekusi,
        LangkahOtomasiPemasaran $langkah,
        KonteksOtomasi $konteks,
        array $konfigurasi,
    ): bool {
        /** @var list<array<string, mixed>> $daftar */
        $daftar = array_values((array) ($konfigurasi['Kondisi'] ?? []));

        if ($this->kondisi->semuaTerpenuhi($daftar, $konteks)) {
            $this->catat($eksekusi, $langkah, HasilLangkahOtomasi::Sukses, 'Kondisi terpenuhi.');
            $this->majukan($eksekusi, $langkah);

            return true;
        }

        $this->catat($eksekusi, $langkah, HasilLangkahOtomasi::KondisiTidakTerpenuhi, 'Eksekusi berhenti.');
        $this->tandai($eksekusi, StatusEksekusiOtomasi::BerhentiKondisi);

        return false;
    }

    /** @param array<string, mixed> $konfigurasi */
    private function jalankanJeda(
        EksekusiOtomasiPemasaran $eksekusi,
        LangkahOtomasiPemasaran $langkah,
        array $konfigurasi,
    ): bool {
        $menit = max((int) ($konfigurasi['Menit'] ?? 0), 0);
        $lanjut = CarbonImmutable::now()->addMinutes($menit);

        // Jeda dicatat sukses supaya percobaan ulang tidak menundanya lagi dari nol.
        $this->catat($eksekusi, $langkah, HasilLangkahOtomasi::Sukses, "Ditunda sampai {$lanjut->toIso8601String()}.");

        $eksekusi->LangkahBerikutnya = $langkah->Urutan + 1;
        $eksekusi->Status = StatusEksekusiOtomasi::Tertunda;
        $eksekusi->LanjutPada = $lanjut;
        $eksekusi->save();

        return false;
    }

    /** @param array<string, mixed> $konfigurasi */
    private function jalankanAksi(
        EksekusiOtomasiPemasaran $eksekusi,
        LangkahOtomasiPemasaran $langkah,
        KonteksOtomasi $konteks,
        array $konfigurasi,
    ): bool {
        $kode = (string) ($konfigurasi['Aksi'] ?? '');
        $tindakan = $this->tindakan->ambil($kode);

        $ringkasan = $tindakan->jalankan(
            $konteks->untukLangkah("eksekusi:{$eksekusi->Id}:langkah:{$langkah->Id}"),
            (array) ($konfigurasi['Konfigurasi'] ?? []),
        );

        $this->catat($eksekusi, $langkah, HasilLangkahOtomasi::Sukses, $ringkasan);
        $this->majukan($eksekusi, $langkah);

        return true;
    }

    private function majukan(EksekusiOtomasiPemasaran $eksekusi, LangkahOtomasiPemasaran $langkah): void
    {
        $eksekusi->LangkahBerikutnya = $langkah->Urutan + 1;
        $eksekusi->Status = StatusEksekusiOtomasi::Berjalan;
        $eksekusi->LanjutPada = null;
        $eksekusi->save();
    }

    /** Percobaan dihitung di baris eksekusinya; setelah capnya habis, barisnya berhenti di DLQ. */
    private function gagalkan(EksekusiOtomasiPemasaran $eksekusi, string $pesan): void
    {
        $percobaan = $eksekusi->Percobaan + 1;
        $cap = max($this->konfigurasi->angka(KatalogKonfigurasiPemasaran::OTOMASI_CAP_PERCOBAAN), 1);

        $eksekusi->Percobaan = $percobaan;
        $eksekusi->Galat = mb_substr($pesan, 0, 500);

        if ($percobaan >= $cap) {
            $eksekusi->Status = StatusEksekusiOtomasi::GagalPermanen;
            $eksekusi->LanjutPada = null;
            $eksekusi->SelesaiPada = CarbonImmutable::now();
        } else {
            $eksekusi->Status = StatusEksekusiOtomasi::Gagal;
            $eksekusi->LanjutPada = CarbonImmutable::now()->addSeconds(60 * (2 ** ($percobaan - 1)));
        }

        $eksekusi->save();
    }

    private function tandai(
        EksekusiOtomasiPemasaran $eksekusi,
        StatusEksekusiOtomasi $status,
    ): StatusEksekusiOtomasi {
        $eksekusi->Status = $status;
        $eksekusi->LanjutPada = null;
        $eksekusi->SelesaiPada = CarbonImmutable::now();
        $eksekusi->save();

        return $status;
    }

    private function catat(
        EksekusiOtomasiPemasaran $eksekusi,
        LangkahOtomasiPemasaran $langkah,
        HasilLangkahOtomasi $hasil,
        ?string $ringkasan,
    ): void {
        LogEksekusiOtomasi::create([
            'EksekusiOtomasiPemasaranId' => $eksekusi->Id,
            'LangkahOtomasiPemasaranId' => $langkah->Id,
            'Urutan' => $langkah->Urutan,
            'Jenis' => $langkah->Jenis->value,
            'Hasil' => $hasil->value,
            'Ringkasan' => $ringkasan === null ? null : mb_substr($ringkasan, 0, 500),
            'TerjadiPada' => CarbonImmutable::now(),
        ]);
    }

    /** @return list<LangkahOtomasiPemasaran> */
    private function langkah(EksekusiOtomasiPemasaran $eksekusi): array
    {
        return array_values(LangkahOtomasiPemasaran::query()
            ->where('VersiOtomasiPemasaranId', $eksekusi->VersiOtomasiPemasaranId)
            ->orderBy('Urutan')
            ->get()
            ->all());
    }

    /** @return list<int> */
    private function urutanSukses(EksekusiOtomasiPemasaran $eksekusi): array
    {
        return array_values(LogEksekusiOtomasi::query()
            ->where('EksekusiOtomasiPemasaranId', $eksekusi->Id)
            ->where('Hasil', HasilLangkahOtomasi::Sukses->value)
            ->pluck('Urutan')
            ->map(intval(...))
            ->all());
    }
}
