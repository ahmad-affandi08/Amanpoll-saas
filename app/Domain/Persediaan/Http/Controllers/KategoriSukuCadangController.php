<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Http\Controllers;

use App\Domain\Persediaan\Application\Actions\BuatKategoriSukuCadang;
use App\Domain\Persediaan\Application\Actions\HapusKategoriSukuCadang;
use App\Domain\Persediaan\Application\Actions\UbahKategoriSukuCadang;
use App\Domain\Persediaan\Http\Requests\SimpanKategoriSukuCadangRequest;
use App\Domain\Persediaan\Http\Resources\KategoriSukuCadangResource;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\KategoriSukuCadang;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class KategoriSukuCadangController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', KategoriSukuCadang::class);

        $kategoriSukuCadang = KategoriSukuCadang::query()->with('induk')->orderBy('Nama')->get();

        return Inertia::render('KategoriSukuCadang/Index', [
            'kategoriSukuCadang' => KategoriSukuCadangResource::collection($kategoriSukuCadang),
        ]);
    }

    public function store(SimpanKategoriSukuCadangRequest $request, BuatKategoriSukuCadang $aksi): RedirectResponse
    {
        $this->authorize('create', KategoriSukuCadang::class);

        $aksi->jalankan($request->validated());

        return back()->with('sukses', 'Kategori suku cadang berhasil dibuat.');
    }

    public function update(SimpanKategoriSukuCadangRequest $request, KategoriSukuCadang $kategoriSukuCadang, UbahKategoriSukuCadang $aksi): RedirectResponse
    {
        $this->authorize('update', $kategoriSukuCadang);

        $aksi->jalankan($kategoriSukuCadang, $request->validated());

        return back()->with('sukses', 'Kategori suku cadang berhasil diperbarui.');
    }

    public function destroy(KategoriSukuCadang $kategoriSukuCadang, HapusKategoriSukuCadang $aksi): RedirectResponse
    {
        $this->authorize('delete', $kategoriSukuCadang);

        $aksi->jalankan($kategoriSukuCadang);

        return back()->with('sukses', 'Kategori suku cadang berhasil dihapus.');
    }
}
