<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Controllers;

use App\Core\Host\PetaHost;
use App\Domain\Pemasaran\Application\Services\PenautHostPengunjung;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Root host publik (MARKETING.md 34.1).
 *
 * Isinya masih kerangka: halaman yang dapat disusun dari dashboard baru lahir
 * di FASE 32. Yang sudah nyata di sini adalah batas hostnya — halaman ini
 * anonim, tidak pernah menyentuh data tenant — dan penyeberangan identitas
 * pengunjung ke host dashboard.
 */
final class BerandaPublikController extends Controller
{
    public function __invoke(Request $request, PetaHost $host, PenautHostPengunjung $penaut): Response
    {
        $pengenal = $request->attributes->get('pengenalPengunjung');
        $pengenal = is_string($pengenal) ? $pengenal : null;

        return Inertia::render('Publik/Beranda', [
            'kanonik' => $host->urlKanonik('/'),
            'urlMasuk' => $penaut->tautan(route('login'), $pengenal),
            'urlDaftar' => $penaut->tautan(route('login'), $pengenal),
        ]);
    }
}
