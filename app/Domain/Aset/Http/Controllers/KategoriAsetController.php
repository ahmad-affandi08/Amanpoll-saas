<?php

declare(strict_types=1);

namespace App\Domain\Aset\Http\Controllers;

use App\Domain\Aset\Application\Actions\BuatKategoriAset;
use App\Domain\Aset\Application\Actions\HapusKategoriAset;
use App\Domain\Aset\Application\Actions\UbahKategoriAset;
use App\Domain\Aset\Http\Requests\SimpanKategoriAsetRequest;
use App\Domain\Aset\Http\Resources\KategoriAsetResource;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Persistence\DaftarTersaring;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class KategoriAsetController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', KategoriAset::class);

        $daftar = DaftarTersaring::untuk($request, KategoriAset::query()->with('induk'))
            ->cari(['Kode', 'Nama'])
            ->urut(['Nama', 'Kode'], bawaan: 'Nama');

        return Inertia::render('KategoriAset/Index', [
            'kategoriAset' => KategoriAsetResource::collection($daftar->halaman()),
            'filter' => $daftar->filterBerlaku(),
            // Pemilih induk harus memuat seluruh kategori, bukan hanya yang tampil di halaman ini.
            'pilihanInduk' => KategoriAset::query()->orderBy('Nama')->get(['Id', 'Nama']),
        ]);
    }

    public function store(SimpanKategoriAsetRequest $request, BuatKategoriAset $aksi): RedirectResponse
    {
        $this->authorize('create', KategoriAset::class);

        $aksi->jalankan($request->validated());

        return back()->with('sukses', 'Kategori aset berhasil dibuat.');
    }

    public function update(SimpanKategoriAsetRequest $request, KategoriAset $kategoriAset, UbahKategoriAset $aksi): RedirectResponse
    {
        $this->authorize('update', $kategoriAset);

        $aksi->jalankan($kategoriAset, $request->validated());

        return back()->with('sukses', 'Kategori aset berhasil diperbarui.');
    }

    public function destroy(KategoriAset $kategoriAset, HapusKategoriAset $aksi): RedirectResponse
    {
        $this->authorize('delete', $kategoriAset);

        $aksi->jalankan($kategoriAset);

        return back()->with('sukses', 'Kategori aset berhasil dihapus.');
    }
}
