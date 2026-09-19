<?php

declare(strict_types=1);

namespace App\Domain\Penyedia\Http\Controllers;

use App\Domain\Penyedia\Application\Actions\BuatPenyedia;
use App\Domain\Penyedia\Application\Actions\HapusPenyedia;
use App\Domain\Penyedia\Application\Actions\UbahPenyedia;
use App\Domain\Penyedia\Http\Requests\SimpanPenyediaRequest;
use App\Domain\Penyedia\Http\Resources\KategoriPenyediaResource;
use App\Domain\Penyedia\Http\Resources\PenyediaResource;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\KategoriPenyedia;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class PenyediaController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Penyedia::class);

        $penyedia = Penyedia::query()
            ->with('kategoriPenyedia')
            ->orderBy('Nama')
            ->get();

        return Inertia::render('Penyedia/Index', [
            'penyedia' => PenyediaResource::collection($penyedia),
            'kategoriPenyedia' => KategoriPenyediaResource::collection(KategoriPenyedia::query()->orderBy('Nama')->get()),
        ]);
    }

    public function store(SimpanPenyediaRequest $request, BuatPenyedia $aksi): RedirectResponse
    {
        $this->authorize('create', Penyedia::class);

        $aksi->jalankan($request->validated());

        return back()->with('sukses', 'Penyedia berhasil dibuat.');
    }

    public function update(SimpanPenyediaRequest $request, Penyedia $penyedia, UbahPenyedia $aksi): RedirectResponse
    {
        $this->authorize('update', $penyedia);

        $aksi->jalankan($penyedia, $request->validated());

        return back()->with('sukses', 'Penyedia berhasil diperbarui.');
    }

    public function destroy(Penyedia $penyedia, HapusPenyedia $aksi): RedirectResponse
    {
        $this->authorize('delete', $penyedia);

        $aksi->jalankan($penyedia);

        return back()->with('sukses', 'Penyedia berhasil dihapus.');
    }
}
