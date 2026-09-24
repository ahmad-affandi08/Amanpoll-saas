<?php

declare(strict_types=1);

namespace App\Domain\Sinkronisasi\Http\Controllers;

use App\Domain\Platform\Application\Services\PenentuModeLapangan;
use App\Domain\Sinkronisasi\Http\Requests\UbahTampilanLapanganRequest;
use App\Http\Controllers\Controller;
use App\Http\Middleware\ArahkanPenggunaLapangan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cookie;

/**
 * Pengguna campuran beralih antara dasbor dan Mode Lapangan (PRD 8.20).
 *
 * Pilihannya disimpan di cookie, bukan di profil, karena diingat per
 * perangkat: orang yang sama boleh memakai Mode Lapangan di HP dan dasbor di
 * laptop. `ArahkanPenggunaLapangan` membaca cookie ini untuk beranda dasbor.
 * Pengguna lapangan murni dan pengguna meja tidak punya pilihan untuk diubah.
 */
final class LapanganTampilanController extends Controller
{
    /** Lima tahun: pilihan tampilan bertahan sampai pengguna mengubahnya sendiri. */
    private const UMUR_COOKIE_MENIT = 60 * 24 * 365 * 5;

    public function __invoke(UbahTampilanLapanganRequest $request, PenentuModeLapangan $penentuModeLapangan): RedirectResponse
    {
        abort_unless(
            $penentuModeLapangan->bisaBeralih($request->user('web')),
            403,
            'Tampilan hanya dapat dipilih pengguna yang memegang peran lapangan dan peran dasbor sekaligus.',
        );

        $tampilan = $request->validated('Tampilan');
        $tujuan = $tampilan === ArahkanPenggunaLapangan::TAMPILAN_LAPANGAN
            ? redirect()->route('lapangan.beranda')
            : redirect()->route('dashboard');

        return $tujuan->withCookie(Cookie::make(
            name: ArahkanPenggunaLapangan::COOKIE_TAMPILAN,
            value: $tampilan,
            minutes: self::UMUR_COOKIE_MENIT,
            httpOnly: true,
            sameSite: 'lax',
        ));
    }
}
