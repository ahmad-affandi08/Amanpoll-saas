<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Controllers;

use App\Domain\Platform\Application\Actions\BuatHariLibur;
use App\Domain\Platform\Application\Actions\HapusHariLibur;
use App\Domain\Platform\Application\Actions\UbahHariLibur;
use App\Domain\Platform\Http\Requests\SimpanHariLiburRequest;
use App\Domain\Platform\Http\Resources\HariLiburResource;
use App\Domain\Platform\Infrastructure\Persistence\Models\HariLibur;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Persistence\DaftarTersaring;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class HariLiburController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', HariLibur::class);

        $daftar = DaftarTersaring::untuk($request, HariLibur::query())
            ->cari(['Nama'])
            ->urut(['Tanggal', 'Nama'], bawaan: 'Tanggal', arahBawaan: 'desc')
            ->faset(['BerulangTahunan']);

        return Inertia::render('HariLibur/Index', [
            'hariLibur' => HariLiburResource::collection($daftar->halaman()),
            'filter' => $daftar->filterBerlaku(),
        ]);
    }

    public function store(SimpanHariLiburRequest $request, BuatHariLibur $aksi): RedirectResponse
    {
        $this->authorize('create', HariLibur::class);

        $aksi->jalankan($request->validated());

        return back()->with('sukses', 'Hari libur berhasil ditambahkan.');
    }

    public function update(SimpanHariLiburRequest $request, HariLibur $hariLibur, UbahHariLibur $aksi): RedirectResponse
    {
        $this->authorize('update', $hariLibur);

        $aksi->jalankan($hariLibur, $request->validated());

        return back()->with('sukses', 'Hari libur berhasil diperbarui.');
    }

    public function destroy(HariLibur $hariLibur, HapusHariLibur $aksi): RedirectResponse
    {
        $this->authorize('delete', $hariLibur);

        $aksi->jalankan($hariLibur);

        return back()->with('sukses', 'Hari libur berhasil dihapus.');
    }
}
