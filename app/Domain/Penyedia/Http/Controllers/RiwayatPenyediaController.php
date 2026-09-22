<?php

declare(strict_types=1);

namespace App\Domain\Penyedia\Http\Controllers;

use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\PelaksanaanKalibrasi;
use App\Domain\Kontrak\Domain\Enums\StatusKontrak;
use App\Domain\Kontrak\Infrastructure\Persistence\Models\Kontrak;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia;
use App\Domain\PerencanaanPengadaan\Domain\Enums\StatusPenawaranPenyedia;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PenawaranPenyedia;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PesananPembelian;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\TagihanPenyedia;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Rekam jejak satu penyedia, dibaca tab Pengadaan dan Layanan.
 *
 * Penawaran, pesanan, tagihan, kontrak, aset, dan kalibrasi penyedia sudah
 * lama tercatat di enam tabel, tetapi tidak pernah dikumpulkan di halaman
 * penyedianya sendiri -- untuk menjawab "kita sudah belanja berapa di sini"
 * orang harus membuka tiap modul lalu menyaring satu per satu.
 */
final class RiwayatPenyediaController extends Controller
{
    /** Riwayat penyedia lama dapat mencapai ratusan baris; yang ditampilkan dibatasi dan dikatakan jumlahnya. */
    private const MAKS_BARIS = 50;

    public function pengadaan(Penyedia $penyedia): JsonResponse
    {
        $this->authorize('view', $penyedia);

        $totalPenawaran = $penyedia->penawaran()->count();
        $totalPesanan = $penyedia->pesananPembelian()->count();
        $totalTagihan = $penyedia->tagihan()->count();

        return response()->json([
            'ringkasan' => [
                'JumlahPenawaran' => $totalPenawaran,
                'JumlahPenawaranTerpilih' => $penyedia->penawaran()->where('Status', StatusPenawaranPenyedia::Terpilih->value)->count(),
                'JumlahPesanan' => $totalPesanan,
                'NilaiPesanan' => (float) $penyedia->pesananPembelian()->sum('Total'),
                'JumlahTagihan' => $totalTagihan,
                'NilaiTagihan' => (float) $penyedia->tagihan()->sum('Total'),
                'SisaTagihan' => (float) $penyedia->tagihan()->sum('Sisa'),
            ],
            'penawaran' => [
                'total' => $totalPenawaran,
                'data' => $penyedia->penawaran()
                    ->with('permintaanPenawaran')
                    ->limit(self::MAKS_BARIS)
                    ->get()
                    ->map(fn (PenawaranPenyedia $satu): array => [
                        'Id' => $satu->Id,
                        'NomorPenawaran' => $satu->NomorPenawaran,
                        'PermintaanPenawaran' => $satu->permintaanPenawaran?->Nomor,
                        'Status' => $satu->Status,
                        'Total' => (float) $satu->Total,
                        'MataUang' => $satu->MataUang,
                        'TanggalPenawaran' => $satu->TanggalPenawaran->toDateString(),
                        'BerlakuSampai' => $satu->BerlakuSampai?->toDateString(),
                    ])
                    ->all(),
            ],
            'pesanan' => [
                'total' => $totalPesanan,
                'data' => $penyedia->pesananPembelian()
                    ->limit(self::MAKS_BARIS)
                    ->get()
                    ->map(fn (PesananPembelian $satu): array => [
                        'Id' => $satu->Id,
                        'Nomor' => $satu->Nomor,
                        'Status' => $satu->Status,
                        'Total' => (float) $satu->Total,
                        'MataUang' => $satu->MataUang,
                        'TanggalPesanan' => $satu->TanggalPesanan->toDateString(),
                        'TanggalKirimRencana' => $satu->TanggalKirimRencana?->toDateString(),
                    ])
                    ->all(),
            ],
            'tagihan' => [
                'total' => $totalTagihan,
                'data' => $penyedia->tagihan()
                    ->with('pesananPembelian')
                    ->limit(self::MAKS_BARIS)
                    ->get()
                    ->map(fn (TagihanPenyedia $satu): array => [
                        'Id' => $satu->Id,
                        'NomorTagihan' => $satu->NomorTagihan,
                        'NomorPesanan' => $satu->pesananPembelian?->Nomor,
                        'Status' => $satu->Status,
                        'Total' => (float) $satu->Total,
                        'Sisa' => (float) $satu->Sisa,
                        'TanggalTagihan' => $satu->TanggalTagihan->toDateString(),
                        'JatuhTempo' => $satu->JatuhTempo?->toDateString(),
                    ])
                    ->all(),
            ],
        ]);
    }

    public function layanan(Penyedia $penyedia): JsonResponse
    {
        $this->authorize('view', $penyedia);

        $totalKontrak = $penyedia->kontrak()->count();
        $totalAset = $penyedia->asetDipasok()->count();
        $totalKalibrasi = $penyedia->pelaksanaanKalibrasi()->count();

        return response()->json([
            'ringkasan' => [
                'JumlahKontrak' => $totalKontrak,
                'JumlahKontrakAktif' => $penyedia->kontrak()->where('Status', StatusKontrak::Aktif->value)->count(),
                'NilaiKontrakAktif' => (float) $penyedia->kontrak()->where('Status', StatusKontrak::Aktif->value)->sum('Nilai'),
                'JumlahAset' => $totalAset,
                'JumlahKalibrasi' => $totalKalibrasi,
            ],
            'kontrak' => [
                'total' => $totalKontrak,
                'data' => $penyedia->kontrak()
                    ->with('tingkatLayanan')
                    ->limit(self::MAKS_BARIS)
                    ->get()
                    ->map(fn (Kontrak $satu): array => [
                        'Id' => $satu->Id,
                        'Nomor' => $satu->Nomor,
                        'Nama' => $satu->Nama,
                        'Jenis' => $satu->Jenis,
                        'Status' => $satu->Status,
                        'Nilai' => $satu->Nilai === null ? null : (float) $satu->Nilai,
                        'MataUang' => $satu->MataUang,
                        'TingkatLayanan' => $satu->tingkatLayanan?->Nama,
                        'MulaiPada' => $satu->MulaiPada->toDateString(),
                        'BerakhirPada' => $satu->BerakhirPada->toDateString(),
                    ])
                    ->all(),
            ],
            'aset' => [
                'total' => $totalAset,
                'data' => $penyedia->asetDipasok()
                    ->with('kategoriAset')
                    ->limit(self::MAKS_BARIS)
                    ->get()
                    ->map(fn (Aset $satu): array => [
                        'Id' => $satu->Id,
                        'KodeAset' => $satu->KodeAset,
                        'Nama' => $satu->Nama,
                        'Kategori' => $satu->kategoriAset?->Nama,
                        'Status' => $satu->Status,
                        'Kondisi' => $satu->Kondisi,
                        'HargaPerolehan' => $satu->HargaPerolehan === null ? null : (float) $satu->HargaPerolehan,
                        'TanggalPerolehan' => $satu->TanggalPerolehan?->toDateString(),
                    ])
                    ->all(),
            ],
            'kalibrasi' => [
                'total' => $totalKalibrasi,
                'data' => $penyedia->pelaksanaanKalibrasi()
                    ->with(['aset', 'jenisKalibrasi'])
                    ->limit(self::MAKS_BARIS)
                    ->get()
                    ->map(fn (PelaksanaanKalibrasi $satu): array => [
                        'Id' => $satu->Id,
                        'Nomor' => $satu->Nomor,
                        'Aset' => $satu->aset?->Nama,
                        'AsetId' => $satu->AsetId,
                        'JenisKalibrasi' => $satu->jenisKalibrasi?->Nama,
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
