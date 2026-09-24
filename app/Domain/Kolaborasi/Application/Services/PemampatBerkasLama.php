<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Application\Services;

use App\Core\Audit\LayananAudit;
use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\Berkas;
use App\Shared\Infrastructure\Kompresi\MetodeKompresi;
use Illuminate\Database\Eloquent\Builder;

/**
 * Memadatkan berkas yang tersimpan sebelum mesin kompresi ada (PRD 11.1,
 * perintah `berkas:pampatkan`). Dijalankan eksplisit per organisasi, bukan
 * otomatis saat deploy.
 *
 * Idempoten: yang disentuh hanya baris bermetode `Tidak` yang ukuran
 * tersimpannya belum lebih kecil dari aslinya dan jenisnya memang dapat
 * dipadatkan mesin. Baris dibaca per potongan; satu salinan fisik yang dipakai
 * beberapa baris hanya diproses sekali dan seluruh barisnya diperbarui bersama.
 */
final class PemampatBerkasLama
{
    private const UKURAN_POTONGAN = 100;

    public function __construct(
        private readonly PenyimpanBerkas $penyimpan,
        private readonly KonteksOrganisasi $konteks,
        private readonly LayananAudit $audit,
    ) {}

    /**
     * @param  ?int  $batas  Paling banyak salinan fisik yang diperiksa; null tanpa batas.
     * @param  (callable(Berkas, string): void)|null  $saatGagal  Dipanggil untuk tiap salinan yang gagal, dengan pesannya.
     * @return array{Diperiksa: int, Dipadatkan: int, Thumbnail: int, Tetap: int, Gagal: int, JumlahBaris: int, UkuranSebelum: int, UkuranSesudah: int}
     */
    public function jalankan(string $organisasiId, bool $pratinjau = false, ?int $batas = null, ?callable $saatGagal = null): array
    {
        $ringkasan = [
            'Diperiksa' => 0, 'Dipadatkan' => 0, 'Thumbnail' => 0, 'Tetap' => 0, 'Gagal' => 0,
            'JumlahBaris' => 0, 'UkuranSebelum' => 0, 'UkuranSesudah' => 0,
        ];
        $kolomHasil = ['dipadatkan' => 'Dipadatkan', 'thumbnail' => 'Thumbnail', 'tetap' => 'Tetap', 'gagal' => 'Gagal'];
        $sudahDilihat = [];

        $this->konteks->tetapkan($organisasiId);

        try {
            foreach ($this->kandidat()->lazyById(self::UKURAN_POTONGAN, 'Id') as $berkas) {
                if ($batas !== null && $ringkasan['Diperiksa'] >= $batas) {
                    break;
                }

                $kunci = $berkas->MediaPenyimpanan."\0".$berkas->LokasiPenyimpanan;
                if (isset($sudahDilihat[$kunci])) {
                    continue;
                }
                $sudahDilihat[$kunci] = true;

                $hasil = $this->penyimpan->pampatkanUlang($berkas, $pratinjau);

                $ringkasan['Diperiksa']++;
                $ringkasan[$kolomHasil[$hasil['Hasil']]]++;
                $ringkasan['UkuranSebelum'] += $hasil['UkuranSebelum'];
                $ringkasan['UkuranSesudah'] += $hasil['UkuranSesudah'];
                if ($hasil['Hasil'] === 'dipadatkan' || $hasil['Hasil'] === 'thumbnail') {
                    $ringkasan['JumlahBaris'] += $hasil['JumlahBaris'];
                }

                if ($hasil['Hasil'] === 'gagal' && $saatGagal !== null) {
                    $saatGagal($berkas, (string) $hasil['Pesan']);
                }
            }

            if (! $pratinjau && $ringkasan['Dipadatkan'] + $ringkasan['Thumbnail'] + $ringkasan['Gagal'] > 0) {
                $this->audit->catat('Berkas.DipadatkanUlang', 'Organisasi', $organisasiId, dataSesudah: $ringkasan);
            }
        } finally {
            $this->konteks->bersihkan();
        }

        return $ringkasan;
    }

    /**
     * Baris lama yang masih mungkin dipadatkan. Jenis yang pasti disimpan apa
     * adanya (XLSX, DOCX, ZIP, video) tidak ikut dibaca sama sekali.
     *
     * @return Builder<Berkas>
     */
    private function kandidat(): Builder
    {
        $mimeLain = [
            ...(array) config('amanpoll.kompresi.mime_teks', []),
            ...(array) config('amanpoll.kompresi.mime_gzip_bila_hemat', []),
        ];

        return Berkas::query()
            ->where('MetodeKompresi', MetodeKompresi::Tidak->value)
            ->whereRaw('COALESCE(UkuranTersimpanByte, UkuranByte) >= COALESCE(UkuranAsliByte, UkuranByte)')
            ->where(fn (Builder $kueri) => $kueri
                ->where('JenisMime', 'like', 'image/%')
                ->orWhere('JenisMime', 'like', 'text/%')
                ->orWhereIn('JenisMime', $mimeLain));
    }
}
