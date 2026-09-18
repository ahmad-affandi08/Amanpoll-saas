<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Domain\Platform\Application\Actions\ResetKataSandi;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

final class ResetKataSandiController extends Controller
{
    public function create(string $penggunaId, string $token): Response
    {
        return Inertia::render('Auth/ResetKataSandi', [
            'penggunaId' => $penggunaId,
            'token' => $token,
        ]);
    }

    public function store(Request $request, string $penggunaId, string $token, ResetKataSandi $aksi): RedirectResponse
    {
        $data = $request->validate([
            'KataSandiBaru' => ['required', 'string', Password::min(8), 'confirmed'],
        ]);

        $aksi->jalankan($penggunaId, $token, $data['KataSandiBaru']);

        return redirect()->route('login')->with('sukses', 'Kata sandi berhasil direset. Silakan masuk.');
    }
}
