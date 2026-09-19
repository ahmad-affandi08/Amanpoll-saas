<?php

declare(strict_types=1);

namespace App\Domain\Aset\Http\Controllers;

use App\Domain\Aset\Application\Actions\BuatMeterAset;
use App\Domain\Aset\Application\Actions\UbahMeterAset;
use App\Domain\Aset\Http\Requests\SimpanMeterAsetRequest;
use App\Domain\Aset\Http\Resources\MeterAsetResource;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\MeterAset;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class MeterAsetController extends Controller
{
    public function index(Aset $aset): AnonymousResourceCollection
    {
        $this->authorize('view', $aset);

        $meter = $aset->meterAset()->with('pembacaan')->get();

        return MeterAsetResource::collection($meter);
    }

    public function store(SimpanMeterAsetRequest $request, Aset $aset, BuatMeterAset $aksi): RedirectResponse
    {
        $this->authorize('update', $aset);

        $aksi->jalankan($aset, $request->validated());

        return back()->with('sukses', 'Meter aset berhasil dibuat.');
    }

    public function update(SimpanMeterAsetRequest $request, MeterAset $meterAset, UbahMeterAset $aksi): RedirectResponse
    {
        /** @var Aset $aset */
        $aset = $meterAset->aset;
        $this->authorize('update', $aset);

        $aksi->jalankan($meterAset, $request->validated());

        return back()->with('sukses', 'Meter aset berhasil diperbarui.');
    }
}
