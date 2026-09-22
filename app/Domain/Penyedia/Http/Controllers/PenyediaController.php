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
use App\Shared\Infrastructure\Persistence\DaftarTersaring;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PenyediaController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Penyedia::class);

        $daftar = DaftarTersaring::untuk($request, Penyedia::query()->with('kategoriPenyedia'))
            ->cari(['Kode', 'Nama', 'NamaLegal', 'Email'])
            ->urut(['Nama', 'Kode', 'Status', 'Kota'], bawaan: 'Nama')
            ->faset(['Status'])
            // Kategori penyedia adalah relasi banyak-ke-banyak, bukan kolom Penyedia.
            ->saring('KategoriPenyediaId', function (Builder $kueri, string $nilai): void {
                $kueri->whereHas(
                    'kategoriPenyedia',
                    fn (Builder $kategori) => $kategori->whereIn('KategoriPenyedia.Id', explode(',', $nilai)),
                );
            });

        return Inertia::render('Penyedia/Index', [
            'penyedia' => PenyediaResource::collection($daftar->halaman()),
            'filter' => $daftar->filterBerlaku(),
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
