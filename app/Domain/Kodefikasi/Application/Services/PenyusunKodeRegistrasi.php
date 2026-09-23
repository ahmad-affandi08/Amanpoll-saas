<?php

declare(strict_types=1);

namespace App\Domain\Kodefikasi\Application\Services;

use App\Domain\Kodefikasi\Domain\Enums\StandarKodefikasi;
use App\Domain\Kodefikasi\Infrastructure\Persistence\Models\KodeBarangAset;
use App\Domain\Platform\Infrastructure\Persistence\Models\KonfigurasiOrganisasi;

/**
 * Kode registrasi barang milik negara.
 *
 * Susunannya: kode lokasi (18 angka) + tahun perolehan (4) + kode barang (10)
 * + NUP (6). Kode lokasi adalah identitas satuan kerja dari Kemenkeu, jadi ia
 * disimpan sebagai konfigurasi organisasi -- sistem tidak dapat menurunkannya
 * dari data yang kita punya.
 *
 * Tanpa kode lokasi, kode registrasi tidak diterbitkan setengah jadi: nomor
 * registrasi yang salah lebih berbahaya daripada kolom yang kosong, karena ia
 * terlanjur tercatat di laporan barang milik negara.
 */
final class PenyusunKodeRegistrasi
{
    public const KUNCI_KODE_LOKASI = 'Kodefikasi.KodeLokasiBmn';

    private const PANJANG_KODE_LOKASI = 18;

    public function untuk(KodeBarangAset $penetapan): ?string
    {
        if ($penetapan->Standar !== StandarKodefikasi::SimakBmn) {
            return null;
        }

        $kodeLokasi = $this->kodeLokasi();
        $kodeBarang = $penetapan->kodeBarang?->Kode;
        $tahun = $penetapan->aset?->TanggalPerolehan?->format('Y');

        if ($kodeLokasi === null || $kodeBarang === null || $tahun === null) {
            return null;
        }

        return sprintf(
            '%s.%s.%s.%06d',
            $kodeLokasi,
            $tahun,
            $kodeBarang,
            $penetapan->Nup,
        );
    }

    /** Alasan kode registrasi belum dapat diterbitkan, untuk ditampilkan apa adanya. */
    public function alasanBelumLengkap(KodeBarangAset $penetapan): ?string
    {
        if ($penetapan->Standar !== StandarKodefikasi::SimakBmn) {
            return null;
        }

        if ($this->kodeLokasi() === null) {
            return 'Kode lokasi satuan kerja belum diisi di konfigurasi organisasi.';
        }

        if ($penetapan->aset?->TanggalPerolehan === null) {
            return 'Tanggal perolehan aset belum diisi.';
        }

        return null;
    }

    private function kodeLokasi(): ?string
    {
        $nilai = KonfigurasiOrganisasi::query()
            ->where('Kunci', self::KUNCI_KODE_LOKASI)
            ->value('Nilai');

        if (! is_string($nilai)) {
            return null;
        }

        $bersih = preg_replace('/\D/', '', $nilai) ?? '';

        return strlen($bersih) === self::PANJANG_KODE_LOKASI ? $bersih : null;
    }
}
