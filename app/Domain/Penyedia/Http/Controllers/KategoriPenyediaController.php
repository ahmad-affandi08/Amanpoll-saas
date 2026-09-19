<?php

declare(strict_types=1);

namespace App\Domain\Penyedia\Http\Controllers;

use App\Domain\Penyedia\Application\Actions\BuatKategoriPenyedia;
use App\Domain\Penyedia\Application\Actions\HapusKategoriPenyedia;
use App\Domain\Penyedia\Application\Actions\UbahKategoriPenyedia;
use App\Domain\Penyedia\Http\Requests\SimpanKategoriPenyediaRequest;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\KategoriPenyedia;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

final class KategoriPenyediaController extends Controller
{
    public function store(SimpanKategoriPenyediaRequest $request, BuatKategoriPenyedia $aksi): RedirectResponse
    {
        $this->authorize('create', KategoriPenyedia::class);

        $aksi->jalankan($request->validated());

        return back()->with('sukses', 'Kategori penyedia berhasil dibuat.');
    }

    public function update(SimpanKategoriPenyediaRequest $request, KategoriPenyedia $kategoriPenyedia, UbahKategoriPenyedia $aksi): RedirectResponse
    {
        $this->authorize('update', $kategoriPenyedia);

        $aksi->jalankan($kategoriPenyedia, $request->validated());

        return back()->with('sukses', 'Kategori penyedia berhasil diperbarui.');
    }

    public function destroy(KategoriPenyedia $kategoriPenyedia, HapusKategoriPenyedia $aksi): RedirectResponse
    {
        $this->authorize('delete', $kategoriPenyedia);

        $aksi->jalankan($kategoriPenyedia);

        return back()->with('sukses', 'Kategori penyedia berhasil dihapus.');
    }
}
