<?php

declare(strict_types=1);

namespace App\Domain\Penyedia\Http\Controllers;

use App\Domain\Penyedia\Application\Actions\BuatKontakPenyedia;
use App\Domain\Penyedia\Application\Actions\HapusKontakPenyedia;
use App\Domain\Penyedia\Application\Actions\UbahKontakPenyedia;
use App\Domain\Penyedia\Http\Requests\SimpanKontakPenyediaRequest;
use App\Domain\Penyedia\Http\Resources\KontakPenyediaResource;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\KontakPenyedia;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class KontakPenyediaController extends Controller
{
    public function index(Penyedia $penyedia): AnonymousResourceCollection
    {
        $this->authorize('viewAny', KontakPenyedia::class);

        $kontak = $penyedia->kontakPenyedia()->orderByDesc('Utama')->orderBy('Nama')->get();

        return KontakPenyediaResource::collection($kontak);
    }

    public function store(SimpanKontakPenyediaRequest $request, Penyedia $penyedia, BuatKontakPenyedia $aksi): RedirectResponse
    {
        $this->authorize('create', KontakPenyedia::class);

        $aksi->jalankan($penyedia, $request->validated());

        return back()->with('sukses', 'Kontak penyedia berhasil ditambahkan.');
    }

    public function update(SimpanKontakPenyediaRequest $request, KontakPenyedia $kontakPenyedia, UbahKontakPenyedia $aksi): RedirectResponse
    {
        $this->authorize('update', $kontakPenyedia);

        $aksi->jalankan($kontakPenyedia, $request->validated());

        return back()->with('sukses', 'Kontak penyedia berhasil diperbarui.');
    }

    public function destroy(KontakPenyedia $kontakPenyedia, HapusKontakPenyedia $aksi): RedirectResponse
    {
        $this->authorize('delete', $kontakPenyedia);

        $aksi->jalankan($kontakPenyedia);

        return back()->with('sukses', 'Kontak penyedia berhasil dihapus.');
    }
}
