<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Controllers;

use App\Domain\Pemasaran\Domain\Enums\StatusPartner;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/** Masuk dan keluar portal partner; guardnya terpisah dari tenant maupun admin platform (MARKETING.md 21). */
final class AuthPartnerController extends Controller
{
    private const MAKS_PERCOBAAN = 5;

    private const DURASI_KUNCI_DETIK = 60;

    public function create(): Response
    {
        return Inertia::render('PartnerPemasaran/Login');
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var array{Email: string, KataSandi: string} $data */
        $data = $request->validate([
            'Email' => ['required', 'email'],
            'KataSandi' => ['required', 'string'],
        ]);

        $kunciBatas = Str::lower($data['Email']).'|partner|'.$request->ip();

        if (RateLimiter::tooManyAttempts($kunciBatas, self::MAKS_PERCOBAAN)) {
            $detik = RateLimiter::availableIn($kunciBatas);
            throw ValidationException::withMessages([
                'Email' => "Terlalu banyak percobaan masuk. Coba lagi dalam {$detik} detik.",
            ]);
        }

        // Status ikut menjadi syarat kredensial: partner yang ditangguhkan tidak pernah mendapat sesi.
        $berhasil = Auth::guard('partner')->attempt([
            'EmailPic' => Str::lower($data['Email']),
            'password' => $data['KataSandi'],
            'Status' => StatusPartner::Aktif->value,
        ]);

        if (! $berhasil) {
            RateLimiter::hit($kunciBatas, self::DURASI_KUNCI_DETIK);
            throw ValidationException::withMessages(['Email' => 'Email atau kata sandi tidak sesuai.']);
        }

        RateLimiter::clear($kunciBatas);
        $request->session()->regenerate();

        $partner = Auth::guard('partner')->user();

        if ($partner !== null) {
            $partner->forceFill(['TerakhirMasukPada' => now()])->save();
        }

        return redirect()->intended(route('partner.beranda'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('partner')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('partner.login');
    }
}
