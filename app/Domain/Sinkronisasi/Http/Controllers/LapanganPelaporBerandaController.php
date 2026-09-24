<?php

declare(strict_types=1);

namespace App\Domain\Sinkronisasi\Http\Controllers;

use App\Domain\Pemeliharaan\Domain\Enums\StatusKeluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KategoriKeluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Domain\Sinkronisasi\Application\Services\PenyusunLayarPelapor;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Beranda Mode Lapangan untuk pelapor (DESIGN §36.7, papan pelapor layar 02):
 * aksi "Laporkan Kerusakan", kategori keluhan, dan laporan aktif miliknya.
 * Laporan yang menunggu konfirmasi (Selesai) didahulukan.
 */
final class LapanganPelaporBerandaController extends Controller
{
    /** Jumlah tiket di bagian "Laporan aktif". */
    private const JUMLAH_LAPORAN_AKTIF = 3;

    /** Kategori di grid kartu apung; sisanya lewat langkah lapor. */
    private const JUMLAH_KATEGORI = 8;

    public function __invoke(Request $request, PenyusunLayarPelapor $penyusun): Response
    {
        $pengguna = $request->user('web');
        $status = [
            StatusKeluhan::Selesai->value,
            ...array_map(fn (StatusKeluhan $satu): string => $satu->value, PenyusunLayarPelapor::STATUS_TERBUKA),
        ];

        $kueriAktif = Keluhan::query()
            ->where('PelaporId', $pengguna->Id)
            ->whereIn('Status', $status);

        $laporanAktif = (clone $kueriAktif)
            ->orderByRaw('CASE WHEN Status = ? THEN 0 ELSE 1 END', [StatusKeluhan::Selesai->value])
            ->latest('DilaporkanPada')
            ->orderBy('Id')
            ->limit(self::JUMLAH_LAPORAN_AKTIF)
            ->get();

        return Inertia::render('Lapangan/Pelapor/Beranda', [
            'lokasi' => $penyusun->ringkasLokasi($penyusun->lokasiSaya($pengguna)),
            'kategori' => KategoriKeluhan::query()
                ->where('Aktif', true)
                ->orderBy('Nama')
                ->orderBy('Id')
                ->limit(self::JUMLAH_KATEGORI + 1)
                ->get(['Id', 'Nama'])
                ->map(fn (KategoriKeluhan $kategori): array => ['Id' => $kategori->Id, 'Nama' => $kategori->Nama])
                ->values()
                ->all(),
            'laporanAktif' => $penyusun->ringkasDaftarLaporan($laporanAktif),
            'jumlahAktif' => (clone $kueriAktif)->count(),
        ]);
    }
}
