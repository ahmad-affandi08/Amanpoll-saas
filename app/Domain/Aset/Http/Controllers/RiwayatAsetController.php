<?php

declare(strict_types=1);

namespace App\Domain\Aset\Http\Controllers;

use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\PelaksanaanKalibrasi;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\RencanaKalibrasi;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\WaktuHentiAset;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\Inspeksi;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Riwayat operasional satu aset, dibaca tab Pemeliharaan dan Kalibrasi.
 *
 * Datanya sudah lama tercatat di enam tabel, tetapi tidak pernah dikumpulkan
 * di halaman asetnya sendiri -- untuk menjawab "alat ini sudah berapa kali
 * rusak" orang harus membuka menu modul lalu menyaring satu per satu.
 */
final class RiwayatAsetController extends Controller
{
    /** Riwayat aset lama dapat mencapai ratusan baris; yang ditampilkan dibatasi dan dikatakan jumlahnya. */
    private const MAKS_BARIS = 50;

    public function pemeliharaan(Aset $aset): JsonResponse
    {
        $this->authorize('view', $aset);

        $totalKeluhan = Keluhan::query()->where('AsetId', $aset->Id)->count();
        $totalPerintahKerja = $aset->perintahKerja()->count();
        $totalInspeksi = Inspeksi::query()->where('AsetId', $aset->Id)->count();
        $henti = WaktuHentiAset::query()->where('AsetId', $aset->Id);

        return response()->json([
            'ringkasan' => [
                'JumlahKeluhan' => $totalKeluhan,
                'JumlahPerintahKerja' => $totalPerintahKerja,
                'JumlahInspeksi' => $totalInspeksi,
                'TotalMenitHenti' => (int) (clone $henti)->sum('DurasiMenit'),
                'TerakhirDikerjakanPada' => $aset->perintahKerja()
                    ->whereNotNull('DiselesaikanPada')
                    ->max('DiselesaikanPada'),
            ],
            'keluhan' => [
                'total' => $totalKeluhan,
                'data' => $aset->keluhan()
                    ->with('kategoriKeluhan')
                    ->limit(self::MAKS_BARIS)
                    ->get()
                    ->map(fn (Keluhan $satu): array => [
                        'Id' => $satu->Id,
                        'Nomor' => $satu->Nomor,
                        'Judul' => $satu->Judul,
                        'Kategori' => $satu->kategoriKeluhan?->Nama,
                        'Prioritas' => $satu->Prioritas,
                        'Status' => $satu->Status,
                        'DilaporkanPada' => $satu->DilaporkanPada->toIso8601String(),
                        'DitutupPada' => $satu->DitutupPada?->toIso8601String(),
                    ])
                    ->all(),
            ],
            'perintahKerja' => [
                'total' => $totalPerintahKerja,
                'data' => $aset->perintahKerja()
                    ->limit(self::MAKS_BARIS)
                    ->get()
                    ->map(fn (PerintahKerja $satu): array => [
                        'Id' => $satu->Id,
                        'Nomor' => $satu->Nomor,
                        'Judul' => $satu->Judul,
                        'Jenis' => $satu->Jenis,
                        'Status' => $satu->Status,
                        'Utama' => (bool) $satu->getAttribute('pivot')?->Utama,
                        'KondisiAwal' => $satu->getAttribute('pivot')?->KondisiAwal,
                        'KondisiAkhir' => $satu->getAttribute('pivot')?->KondisiAkhir,
                        'DijadwalkanMulaiPada' => $satu->DijadwalkanMulaiPada?->toIso8601String(),
                        'DiselesaikanPada' => $satu->DiselesaikanPada?->toIso8601String(),
                    ])
                    ->all(),
            ],
            'inspeksi' => [
                'total' => $totalInspeksi,
                'data' => $aset->inspeksi()
                    ->limit(self::MAKS_BARIS)
                    ->get()
                    ->map(fn (Inspeksi $satu): array => [
                        'Id' => $satu->Id,
                        'Nomor' => $satu->Nomor,
                        'Status' => $satu->Status,
                        'Hasil' => $satu->Hasil,
                        'Temuan' => $satu->Temuan,
                        'DijadwalkanPada' => $satu->DijadwalkanPada?->toIso8601String(),
                        'DilaksanakanPada' => $satu->DilaksanakanPada?->toIso8601String(),
                    ])
                    ->all(),
            ],
            'waktuHenti' => [
                'total' => (clone $henti)->count(),
                'data' => $aset->waktuHenti()
                    ->limit(self::MAKS_BARIS)
                    ->get()
                    ->map(fn (WaktuHentiAset $satu): array => [
                        'Id' => $satu->Id,
                        'Jenis' => $satu->Jenis,
                        'Alasan' => $satu->Alasan,
                        'DurasiMenit' => $satu->DurasiMenit,
                        'MulaiPada' => $satu->MulaiPada->toIso8601String(),
                        'SelesaiPada' => $satu->SelesaiPada?->toIso8601String(),
                    ])
                    ->all(),
            ],
        ]);
    }

    public function kalibrasi(Aset $aset): JsonResponse
    {
        $this->authorize('view', $aset);

        $terakhir = $aset->pelaksanaanKalibrasi()->first();
        $totalPelaksanaan = $aset->pelaksanaanKalibrasi()->count();

        return response()->json([
            'ringkasan' => [
                'JumlahPelaksanaan' => $totalPelaksanaan,
                'TerakhirPada' => $terakhir?->TanggalKalibrasi?->toDateString(),
                'HasilTerakhir' => $terakhir?->Hasil,
                'BerlakuSampai' => $terakhir?->TanggalBerlakuSampai?->toDateString(),
                // Jatuh tempo terdekat dari rencana yang masih aktif.
                'JatuhTempoBerikutnya' => $aset->rencanaKalibrasi()
                    ->where('Aktif', true)
                    ->min('TanggalBerikutnya'),
            ],
            'rencana' => $aset->rencanaKalibrasi()
                ->with(['jenisKalibrasi', 'penyedia'])
                ->get()
                ->map(fn (RencanaKalibrasi $satu): array => [
                    'Id' => $satu->Id,
                    'JenisKalibrasi' => $satu->jenisKalibrasi?->Nama,
                    'Penyedia' => $satu->penyedia?->Nama,
                    'IntervalHari' => $satu->IntervalHari,
                    'TanggalBerikutnya' => $satu->TanggalBerikutnya->toDateString(),
                    'Aktif' => (bool) $satu->Aktif,
                ])
                ->all(),
            'pelaksanaan' => [
                'total' => $totalPelaksanaan,
                'data' => $aset->pelaksanaanKalibrasi()
                    ->with(['jenisKalibrasi', 'penyedia'])
                    ->limit(self::MAKS_BARIS)
                    ->get()
                    ->map(fn (PelaksanaanKalibrasi $satu): array => [
                        'Id' => $satu->Id,
                        'Nomor' => $satu->Nomor,
                        'JenisKalibrasi' => $satu->jenisKalibrasi?->Nama,
                        'Penyedia' => $satu->penyedia?->Nama,
                        'Hasil' => $satu->Hasil,
                        'NomorSertifikat' => $satu->NomorSertifikat,
                        'TanggalKalibrasi' => $satu->TanggalKalibrasi->toDateString(),
                        'TanggalBerlakuSampai' => $satu->TanggalBerlakuSampai?->toDateString(),
                    ])
                    ->all(),
            ],
        ]);
    }
}
