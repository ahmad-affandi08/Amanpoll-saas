<?php

declare(strict_types=1);

namespace App\Domain\Sinkronisasi\Http\Controllers;

use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Domain\Sinkronisasi\Application\Services\PenyusunLayarPelapor;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Pantau laporan rekan (PRD 8.20, TASK 39.10): garis waktu status keluhan orang lain
 * pada alat/lokasi dalam lingkup pelapor, dibuka dari langkah "Alat ditemukan" dan
 * penanda "laporan terbuka" di daftar aset.
 *
 * Keluhan di luar lingkup sudah tidak terikat rute (`ScopeLingkup`, 404) dan
 * `KeluhanPolicy::pantau` menolaknya sekali lagi (403). Keluhan milik sendiri
 * diarahkan ke Lacak laporan yang lengkap.
 */
final class LapanganPelaporPantauController extends Controller
{
    public function __invoke(Request $request, Keluhan $keluhan, PenyusunLayarPelapor $penyusun): Response|RedirectResponse
    {
        $this->authorize('pantau', $keluhan);

        if ($keluhan->PelaporId === $request->user('web')->Id) {
            return redirect()->route('lapangan.pelapor.laporan.show', $keluhan);
        }

        return Inertia::render('Lapangan/Pelapor/Pantau', $penyusun->pantauan($keluhan));
    }
}
