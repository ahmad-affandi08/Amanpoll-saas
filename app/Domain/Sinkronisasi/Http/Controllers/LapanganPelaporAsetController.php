<?php

declare(strict_types=1);

namespace App\Domain\Sinkronisasi\Http\Controllers;

use App\Core\Izin\PemeriksaIzin;
use App\Domain\Sinkronisasi\Application\Services\PenyusunLayarPelapor;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Aset di lokasi pelapor (DESIGN §36.7 layar 14): kondisi, penanda laporan
 * terbuka, dan tombol Lapor per aset. Daftarnya disaring `ScopeLingkup`.
 *
 * Tanpa `Aset.Lihat` (izin yang sama dengan daftar aset dasbor) halamannya tetap
 * terbuka dengan keadaan tanpa izin, supaya tab navigasinya tidak berakhir di
 * halaman galat.
 */
final class LapanganPelaporAsetController extends Controller
{
    public function __invoke(Request $request, PenyusunLayarPelapor $penyusun, PemeriksaIzin $izin): Response
    {
        $pengguna = $request->user('web');
        $bolehLihat = $izin->boleh($pengguna->Id, 'Aset.Lihat');
        $masukan = $request->validate(['lokasi' => ['nullable', 'string', 'max:26']]);

        $lokasi = $bolehLihat
            ? ($penyusun->cariLokasi($masukan['lokasi'] ?? null) ?? $penyusun->lokasiSaya($pengguna))
            : null;

        return Inertia::render('Lapangan/Pelapor/Aset', [
            'bolehLihat' => $bolehLihat,
            'lokasi' => $penyusun->ringkasLokasi($lokasi),
            'pilihanLokasi' => $bolehLihat ? $penyusun->pilihanLokasi() : [],
            'aset' => $lokasi === null ? [] : $penyusun->asetDiLokasi($lokasi, $pengguna),
        ]);
    }
}
