<?php

declare(strict_types=1);

namespace App\Domain\Aset\Http\Controllers;

use App\Domain\Aset\Application\Actions\BuatGaransiAset;
use App\Domain\Aset\Application\Actions\HapusGaransiAset;
use App\Domain\Aset\Application\Actions\UbahGaransiAset;
use App\Domain\Aset\Http\Requests\SimpanGaransiAsetRequest;
use App\Domain\Aset\Http\Resources\GaransiAsetResource;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\GaransiAset;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class GaransiAsetController extends Controller
{
    public function index(Aset $aset): AnonymousResourceCollection
    {
        $this->authorize('view', $aset);

        $garansi = $aset->garansiAset()->with('penyedia')->get();

        return GaransiAsetResource::collection($garansi);
    }

    public function store(SimpanGaransiAsetRequest $request, Aset $aset, BuatGaransiAset $aksi): RedirectResponse
    {
        $this->authorize('update', $aset);

        $aksi->jalankan($aset, $request->validated());

        return back()->with('sukses', 'Garansi aset berhasil ditambahkan.');
    }

    public function update(SimpanGaransiAsetRequest $request, GaransiAset $garansiAset, UbahGaransiAset $aksi): RedirectResponse
    {
        /** @var Aset $aset */
        $aset = $garansiAset->aset;
        $this->authorize('update', $aset);

        $aksi->jalankan($garansiAset, $request->validated());

        return back()->with('sukses', 'Garansi aset berhasil diperbarui.');
    }

    public function destroy(GaransiAset $garansiAset, HapusGaransiAset $aksi): RedirectResponse
    {
        /** @var Aset $aset */
        $aset = $garansiAset->aset;
        $this->authorize('update', $aset);

        $aksi->jalankan($garansiAset);

        return back()->with('sukses', 'Garansi aset berhasil dihapus.');
    }
}
