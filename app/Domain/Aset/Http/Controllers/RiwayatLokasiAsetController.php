<?php

declare(strict_types=1);

namespace App\Domain\Aset\Http\Controllers;

use App\Domain\Aset\Application\Actions\PindahkanLokasiAset;
use App\Domain\Aset\Http\Requests\SimpanRiwayatLokasiAsetRequest;
use App\Domain\Aset\Http\Resources\RiwayatLokasiAsetResource;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class RiwayatLokasiAsetController extends Controller
{
    public function index(Aset $aset): AnonymousResourceCollection
    {
        $this->authorize('view', $aset);

        $riwayat = $aset->riwayatLokasi()->with(['lokasiAsal', 'lokasiTujuan', 'dipindahkanOleh'])->get();

        return RiwayatLokasiAsetResource::collection($riwayat);
    }

    public function store(SimpanRiwayatLokasiAsetRequest $request, Aset $aset, PindahkanLokasiAset $aksi): RedirectResponse
    {
        $this->authorize('update', $aset);

        $data = $request->validated();
        $aksi->jalankan($aset, $data['LokasiTujuanId'] ?? null, $data['Alasan'] ?? null, $request->user()->Id);

        return back()->with('sukses', 'Lokasi aset berhasil dipindahkan.');
    }
}
