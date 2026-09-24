<?php

declare(strict_types=1);

namespace App\Domain\Sinkronisasi\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Layar Akun Mode Lapangan: peran yang dipegang pengguna dan unit kerjanya.
 *
 * Identitas dasar (nama, email, organisasi) sudah ada di prop bersama `auth`.
 *
 * Antrean sinkronisasi dan konflik dibaca klien dari endpoint `/offline/*`
 * yang sudah ada; beralih ke dasbor memakai `lapangan.tampilan` dan keluar
 * memakai alur logout yang sama dengan dasbor (PRD 8.17, 8.20).
 */
final class LapanganAkunController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $pengguna = $request->user('web');

        $peran = DB::table('PenggunaPeran as pp')
            ->join('Peran as p', 'p.Id', '=', 'pp.PeranId')
            ->where('pp.OrganisasiId', $pengguna->OrganisasiId)
            ->where('pp.PenggunaId', $pengguna->Id)
            ->whereNull('p.DihapusPada')
            ->orderBy('p.Nama')
            ->distinct()
            ->pluck('p.Nama')
            ->map(fn (mixed $nama): string => is_string($nama) ? $nama : '')
            ->filter()
            ->values()
            ->all();

        $lokasi = $pengguna->UnitOrganisasiId === null ? null : DB::table('UnitOrganisasi')
            ->where('OrganisasiId', $pengguna->OrganisasiId)
            ->where('Id', $pengguna->UnitOrganisasiId)
            ->value('Nama');

        // Nama prop mengikuti `PropsHalamanAkun` di features/Lapangan/types.ts.
        return Inertia::render('Lapangan/Akun', [
            'namaPeran' => $peran === [] ? null : implode(', ', $peran),
            'lokasi' => is_string($lokasi) ? $lokasi : null,
            'ekstensi' => $pengguna->Telepon,
        ]);
    }
}
