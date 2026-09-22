<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Controllers;

use App\Core\Host\PetaHost;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Root host publik (MARKETING.md 34.1).
 *
 * Isinya masih kerangka: halaman yang dapat disusun dari dashboard baru lahir
 * di FASE 32. Yang sudah nyata di sini adalah batas hostnya — halaman ini
 * anonim, tidak pernah menyentuh data tenant, dan tautan aksinya mengarah ke
 * host dashboard.
 */
final class BerandaPublikController extends Controller
{
    public function __invoke(PetaHost $host): Response
    {
        return Inertia::render('Publik/Beranda', [
            'kanonik' => $host->urlKanonik('/'),
            'urlMasuk' => route('login'),
            'urlDaftar' => route('login'),
        ]);
    }
}
