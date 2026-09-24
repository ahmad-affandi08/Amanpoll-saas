<?php

declare(strict_types=1);

namespace App\Domain\Sinkronisasi\Http\Controllers;

use App\Domain\Platform\Application\Services\PenentuModeLapangan;
use App\Domain\Platform\Domain\Enums\ModeLapangan;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Pintu masuk Mode Lapangan (`/lapangan`): meneruskan ke beranda sesuai mode.
 *
 * Teknisi menang bila pengguna memegang peran Teknisi dan Pelapor sekaligus
 * (PRD 8.20). Pengguna tanpa mode tidak sampai ke sini; `PastikanModeLapangan`
 * sudah mengembalikannya ke dasbor.
 */
final class LapanganBerandaController extends Controller
{
    public function __invoke(Request $request, PenentuModeLapangan $penentuModeLapangan): RedirectResponse
    {
        return $penentuModeLapangan->mode($request->user('web')) === ModeLapangan::Teknisi
            ? redirect()->route('lapangan.teknisi.beranda')
            : redirect()->route('lapangan.pelapor.beranda');
    }
}
