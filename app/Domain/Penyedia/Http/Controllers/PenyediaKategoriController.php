<?php

declare(strict_types=1);

namespace App\Domain\Penyedia\Http\Controllers;

use App\Domain\Penyedia\Application\Actions\LepaskanKategoriDariPenyedia;
use App\Domain\Penyedia\Application\Actions\TambahkanKategoriKePenyedia;
use App\Domain\Penyedia\Http\Requests\SimpanPenyediaKategoriRequest;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\KategoriPenyedia;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

final class PenyediaKategoriController extends Controller
{
    public function store(SimpanPenyediaKategoriRequest $request, Penyedia $penyedia, TambahkanKategoriKePenyedia $aksi): RedirectResponse
    {
        $this->authorize('update', $penyedia);

        $aksi->jalankan($penyedia, $request->validated()['KategoriPenyediaId']);

        return back()->with('sukses', 'Kategori berhasil ditambahkan ke penyedia.');
    }

    public function destroy(Penyedia $penyedia, KategoriPenyedia $kategoriPenyedia, LepaskanKategoriDariPenyedia $aksi): RedirectResponse
    {
        $this->authorize('update', $penyedia);

        $aksi->jalankan($penyedia, $kategoriPenyedia->Id);

        return back()->with('sukses', 'Kategori berhasil dilepas dari penyedia.');
    }
}
