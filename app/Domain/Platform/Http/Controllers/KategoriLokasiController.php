<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Controllers;

use App\Domain\Platform\Application\Actions\BuatKategoriLokasi;
use App\Domain\Platform\Application\Actions\HapusKategoriLokasi;
use App\Domain\Platform\Application\Actions\UbahKategoriLokasi;
use App\Domain\Platform\Http\Requests\SimpanKategoriLokasiRequest;
use App\Domain\Platform\Http\Resources\KategoriLokasiResource;
use App\Domain\Platform\Infrastructure\Persistence\Models\KategoriLokasi;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Persistence\DaftarTersaring;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class KategoriLokasiController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', KategoriLokasi::class);

        $daftar = DaftarTersaring::untuk($request, KategoriLokasi::query())
            ->cari(['Kode', 'Nama', 'Keterangan'])
            ->urut(['Nama', 'Kode'], bawaan: 'Nama');

        return Inertia::render('KategoriLokasi/Index', [
            'kategoriLokasi' => KategoriLokasiResource::collection($daftar->halaman()),
            'filter' => $daftar->filterBerlaku(),
        ]);
    }

    public function store(SimpanKategoriLokasiRequest $request, BuatKategoriLokasi $aksi): RedirectResponse
    {
        $this->authorize('create', KategoriLokasi::class);

        $aksi->jalankan($request->validated());

        return back()->with('sukses', 'Kategori lokasi berhasil dibuat.');
    }

    public function update(SimpanKategoriLokasiRequest $request, KategoriLokasi $kategoriLokasi, UbahKategoriLokasi $aksi): RedirectResponse
    {
        $this->authorize('update', $kategoriLokasi);

        $aksi->jalankan($kategoriLokasi, $request->validated());

        return back()->with('sukses', 'Kategori lokasi berhasil diperbarui.');
    }

    public function destroy(KategoriLokasi $kategoriLokasi, HapusKategoriLokasi $aksi): RedirectResponse
    {
        $this->authorize('delete', $kategoriLokasi);

        $aksi->jalankan($kategoriLokasi);

        return back()->with('sukses', 'Kategori lokasi berhasil dihapus.');
    }
}
