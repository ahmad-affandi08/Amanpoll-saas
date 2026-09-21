<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Masuk dan keluar admin platform (22.02).
 *
 * Memakai guard tersendiri, sehingga sesi tenant yang sedang berjalan tidak
 * tergeser saat admin platform masuk di peramban yang sama, dan sebaliknya.
 */
final class AuthPlatformController extends Controller
{
    private const MAKS_PERCOBAAN = 5;

    private const DURASI_KUNCI_DETIK = 60;

    public function create(): Response
    {
        return Inertia::render('Platform/Login');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'Email' => ['required', 'email'],
            'KataSandi' => ['required', 'string'],
        ]);

        $kunciBatas = Str::lower($data['Email']).'|platform|'.$request->ip();

        if (RateLimiter::tooManyAttempts($kunciBatas, self::MAKS_PERCOBAAN)) {
            $detik = RateLimiter::availableIn($kunciBatas);
            throw ValidationException::withMessages([
                'Email' => "Terlalu banyak percobaan masuk. Coba lagi dalam {$detik} detik.",
            ]);
        }

        $berhasil = Auth::guard('platform')->attempt([
            'Email' => $data['Email'],
            'password' => $data['KataSandi'],
            'Status' => 'Aktif',
        ]);

        if (! $berhasil) {
            RateLimiter::hit($kunciBatas, self::DURASI_KUNCI_DETIK);
            throw ValidationException::withMessages(['Email' => 'Email atau kata sandi tidak sesuai.']);
        }

        RateLimiter::clear($kunciBatas);
        $request->session()->regenerate();

        $admin = Auth::guard('platform')->user();
        if ($admin !== null) {
            $admin->forceFill(['TerakhirMasukPada' => now()])->save();
        }

        return redirect()->intended(route('adminPlatform.paket.index'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('platform')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('adminPlatform.login');
    }
}
