<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Http\Controllers;

use App\Domain\Pemeliharaan\Application\Actions\HapusKategoriKeluhan;
use App\Domain\Pemeliharaan\Application\Actions\SimpanKategoriKeluhan;
use App\Domain\Pemeliharaan\Http\Requests\SimpanKategoriKeluhanRequest;
use App\Domain\Pemeliharaan\Http\Resources\KategoriKeluhanResource;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KategoriKeluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\TingkatLayanan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class KategoriKeluhanController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', KategoriKeluhan::class);

        return Inertia::render('KategoriKeluhan/Index', [
            'kategori' => KategoriKeluhanResource::collection(KategoriKeluhan::query()->with(['induk', 'tingkatLayanan', 'peranPenanggungJawab'])->orderBy('Nama')->get()),
            'tingkatLayanan' => TingkatLayanan::query()->where('Aktif', true)->orderBy('Nama')->get(['Id', 'Nama']),
            'peran' => Peran::query()->orderBy('Nama')->get(['Id', 'Nama']),
        ]);
    }

    public function store(SimpanKategoriKeluhanRequest $request, SimpanKategoriKeluhan $aksi): RedirectResponse
    {
        $this->authorize('create', KategoriKeluhan::class);
        $aksi->jalankan($request->validated());

        return back()->with('sukses', 'Kategori keluhan berhasil dibuat.');
    }

    public function update(SimpanKategoriKeluhanRequest $request, KategoriKeluhan $kategoriKeluhan, SimpanKategoriKeluhan $aksi): RedirectResponse
    {
        $this->authorize('update', $kategoriKeluhan);
        $aksi->jalankan($request->validated(), $kategoriKeluhan);

        return back()->with('sukses', 'Kategori keluhan berhasil diperbarui.');
    }

    public function destroy(KategoriKeluhan $kategoriKeluhan, HapusKategoriKeluhan $aksi): RedirectResponse
    {
        $this->authorize('delete', $kategoriKeluhan);
        $aksi->jalankan($kategoriKeluhan);

        return back()->with('sukses', 'Kategori keluhan berhasil dihapus.');
    }
}
