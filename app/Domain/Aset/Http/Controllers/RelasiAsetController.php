<?php

declare(strict_types=1);

namespace App\Domain\Aset\Http\Controllers;

use App\Domain\Aset\Application\Actions\BuatRelasiAset;
use App\Domain\Aset\Application\Actions\HapusRelasiAset;
use App\Domain\Aset\Http\Requests\SimpanRelasiAsetRequest;
use App\Domain\Aset\Http\Resources\RelasiAsetResource;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\RelasiAset;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

final class RelasiAsetController extends Controller
{
    public function index(Aset $aset): JsonResponse
    {
        $this->authorize('view', $aset);

        return response()->json([
            'sebagaiInduk' => RelasiAsetResource::collection($aset->relasiSebagaiInduk()->with('asetAnak')->get()),
            'sebagaiAnak' => RelasiAsetResource::collection($aset->relasiSebagaiAnak()->with('asetInduk')->get()),
        ]);
    }

    public function store(SimpanRelasiAsetRequest $request, Aset $aset, BuatRelasiAset $aksi): RedirectResponse
    {
        $this->authorize('update', $aset);

        $data = $request->validated();
        $data['AsetIndukId'] = $aset->Id;
        $aksi->jalankan($data);

        return back()->with('sukses', 'Relasi aset berhasil ditambahkan.');
    }

    public function destroy(RelasiAset $relasiAset, HapusRelasiAset $aksi): RedirectResponse
    {
        /** @var Aset $asetInduk */
        $asetInduk = $relasiAset->asetInduk;
        $this->authorize('update', $asetInduk);

        $aksi->jalankan($relasiAset);

        return back()->with('sukses', 'Relasi aset berhasil dihapus.');
    }
}
