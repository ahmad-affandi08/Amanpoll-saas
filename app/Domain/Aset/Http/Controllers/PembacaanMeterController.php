<?php

declare(strict_types=1);

namespace App\Domain\Aset\Http\Controllers;

use App\Domain\Aset\Application\Actions\CatatPembacaanMeter;
use App\Domain\Aset\Http\Requests\SimpanPembacaanMeterAsetRequest;
use App\Domain\Aset\Http\Resources\PembacaanMeterAsetResource;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\MeterAset;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class PembacaanMeterController extends Controller
{
    public function index(MeterAset $meterAset): AnonymousResourceCollection
    {
        /** @var Aset $aset */
        $aset = $meterAset->aset;
        $this->authorize('view', $aset);

        return PembacaanMeterAsetResource::collection($meterAset->pembacaan()->with('dicatatOleh')->get());
    }

    public function store(SimpanPembacaanMeterAsetRequest $request, MeterAset $meterAset, CatatPembacaanMeter $aksi): RedirectResponse
    {
        /** @var Aset $aset */
        $aset = $meterAset->aset;
        $this->authorize('update', $aset);

        $aksi->jalankan($meterAset, $request->validated(), $request->user('web')->Id);

        return back()->with('sukses', 'Pembacaan meter berhasil dicatat.');
    }
}
