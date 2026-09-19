<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Core\Audit\LayananCatatanAkses;
use App\Core\Organisasi\KonteksOrganisasi;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class LoginController extends Controller
{
    private const MAKS_PERCOBAAN = 5;
    private const DURASI_KUNCI_DETIK = 60;

    public function __construct(
        private readonly KonteksOrganisasi $konteks,
        private readonly LayananCatatanAkses $layananCatatanAkses,
    ) {}

    public function create(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'KodeOrganisasi' => ['required', 'string', 'max:50'],
            'Email' => ['required', 'email'],
            'KataSandi' => ['required', 'string'],
        ]);

        $kunciBatas = $this->kunciBatasPercobaan($request, $data['KodeOrganisasi'], $data['Email']);

        if (RateLimiter::tooManyAttempts($kunciBatas, self::MAKS_PERCOBAAN)) {
            $detik = RateLimiter::availableIn($kunciBatas);
            throw ValidationException::withMessages([
                'Email' => "Terlalu banyak percobaan masuk. Coba lagi dalam {$detik} detik.",
            ]);
        }

        $organisasi = DB::table('Organisasi')
            ->where('Kode', $data['KodeOrganisasi'])
            ->where('Status', 'Aktif')
            ->first(['Id']);

        if (!$organisasi) {
            RateLimiter::hit($kunciBatas, self::DURASI_KUNCI_DETIK);
            $this->layananCatatanAkses->catat('Login', null, null, false, 'Kode organisasi tidak ditemukan/nonaktif.');
            throw ValidationException::withMessages([
                'Email' => 'Organisasi, email, atau kata sandi tidak sesuai.',
            ]);
        }

        $this->konteks->tetapkan((string) $organisasi->Id);

        $berhasil = Auth::attempt(
            ['OrganisasiId' => (string) $organisasi->Id, 'Email' => $data['Email'], 'password' => $data['KataSandi'], 'Status' => 'Aktif'],
            $request->boolean('IngatSaya'),
        );

        if (!$berhasil) {
            RateLimiter::hit($kunciBatas, self::DURASI_KUNCI_DETIK);
            $this->layananCatatanAkses->catat('Login', (string) $organisasi->Id, null, false, 'Email atau kata sandi salah.');
            $this->konteks->bersihkan();
            throw ValidationException::withMessages([
                'Email' => 'Organisasi, email, atau kata sandi tidak sesuai.',
            ]);
        }

        RateLimiter::clear($kunciBatas);
        $request->session()->regenerate();
        DB::table('Pengguna')->where('Id', $request->user()->Id)->update(['TerakhirMasukPada' => now()]);
        $this->layananCatatanAkses->catat('Login', (string) $organisasi->Id, (string) $request->user()->Id, true);

        return redirect()->intended(route('dashboard'));
    }

    private function kunciBatasPercobaan(Request $request, string $kodeOrganisasi, string $email): string
    {
        return Str::lower("{$kodeOrganisasi}|{$email}").'|'.$request->ip();
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
