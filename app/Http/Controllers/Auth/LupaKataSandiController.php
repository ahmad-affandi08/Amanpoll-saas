<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Domain\Platform\Application\Actions\MintaResetKataSandi;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;
use Inertia\Response;

final class LupaKataSandiController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/LupaKataSandi');
    }

    public function store(Request $request, MintaResetKataSandi $aksi): RedirectResponse
    {
        $data = $request->validate([
            'KodeOrganisasi' => ['required', 'string', 'max:50'],
            'Email' => ['required', 'email'],
        ]);

        $kunci = 'lupa-kata-sandi:'.$request->ip();
        if (RateLimiter::tooManyAttempts($kunci, 5)) {
            return back()->with('sukses', 'Bila akun ditemukan, tautan reset kata sandi sudah dikirim.');
        }
        RateLimiter::hit($kunci, 60);

        $aksi->jalankan($data['KodeOrganisasi'], $data['Email']);

        return back()->with('sukses', 'Bila akun ditemukan, tautan reset kata sandi sudah dikirim.');
    }
}
