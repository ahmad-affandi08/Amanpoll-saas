<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Controllers;

use App\Domain\Platform\Application\Actions\GantiKataSandiSendiri;
use App\Domain\Platform\Application\Actions\HapusPerangkatSendiri;
use App\Domain\Platform\Application\Actions\UbahProfilSendiri;
use App\Domain\Platform\Http\Requests\GantiKataSandiRequest;
use App\Domain\Platform\Http\Requests\UbahProfilRequest;
use App\Domain\Platform\Http\Resources\PenggunaResource;
use App\Domain\Platform\Infrastructure\Persistence\Models\PerangkatPengguna;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Validasi\AturanWajib;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ProfilController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('Profil/Index', [
            'wajib' => ['profil' => AturanWajib::untuk(UbahProfilRequest::class), 'kataSandi' => AturanWajib::untuk(GantiKataSandiRequest::class)],
            'pengguna' => new PenggunaResource($request->user('web')->load('perangkat')),
        ]);
    }

    public function update(UbahProfilRequest $request, UbahProfilSendiri $aksi): RedirectResponse
    {
        $data = $request->validated();
        $aksi->jalankan($request->user('web'), $data['Nama'], $data['Email'], $data['Telepon'] ?? null);

        return back()->with('sukses', 'Profil berhasil diperbarui.');
    }

    public function gantiKataSandi(GantiKataSandiRequest $request, GantiKataSandiSendiri $aksi): RedirectResponse
    {
        $aksi->jalankan($request->user('web'), $request->validated()['KataSandiBaru']);

        return back()->with('sukses', 'Kata sandi berhasil diganti.');
    }

    public function hapusPerangkat(Request $request, PerangkatPengguna $perangkat, HapusPerangkatSendiri $aksi): RedirectResponse
    {
        $aksi->jalankan($request->user('web'), $perangkat);

        return back()->with('sukses', 'Perangkat berhasil dihapus.');
    }
}
