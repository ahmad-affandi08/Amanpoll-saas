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
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class HariLiburController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', HariLibur::class);

        $hariLibur = HariLibur::query()->orderBy('Tanggal')->get();

        return Inertia::render('HariLibur/Index', [
            'hariLibur' => HariLiburResource::collection($hariLibur),
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
