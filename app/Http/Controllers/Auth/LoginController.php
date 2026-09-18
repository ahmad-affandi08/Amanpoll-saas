<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class LoginController extends Controller
{
    public function __construct(private readonly KonteksOrganisasi $konteks) {}

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

        $organisasi = DB::table('Organisasi')
            ->where('Kode', $data['KodeOrganisasi'])
            ->where('Status', 'Aktif')
            ->first(['Id']);

        if (!$organisasi) {
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
            $this->konteks->bersihkan();
            throw ValidationException::withMessages([
                'Email' => 'Organisasi, email, atau kata sandi tidak sesuai.',
            ]);
        }

        $request->session()->regenerate();
        DB::table('Pengguna')->where('Id', $request->user()->Id)->update(['TerakhirMasukPada' => now()]);

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
