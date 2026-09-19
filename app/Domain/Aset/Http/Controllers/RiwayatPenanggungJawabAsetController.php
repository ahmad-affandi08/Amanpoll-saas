<?php

declare(strict_types=1);

namespace App\Domain\Aset\Http\Controllers;

use App\Domain\Aset\Application\Actions\AssignPenanggungJawabAset;
use App\Domain\Aset\Http\Requests\SimpanRiwayatPenanggungJawabAsetRequest;
use App\Domain\Aset\Http\Resources\RiwayatPenanggungJawabAsetResource;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class RiwayatPenanggungJawabAsetController extends Controller
{
    public function index(Aset $aset): AnonymousResourceCollection
    {
        $this->authorize('view', $aset);

        $riwayat = $aset->riwayatPenanggungJawab()->with(['pengguna', 'unitOrganisasi'])->get();

        return RiwayatPenanggungJawabAsetResource::collection($riwayat);
    }

    public function store(SimpanRiwayatPenanggungJawabAsetRequest $request, Aset $aset, AssignPenanggungJawabAset $aksi): RedirectResponse
    {
        $this->authorize('update', $aset);

        $aksi->jalankan($aset, $request->validated());

        return back()->with('sukses', 'Penanggung jawab aset berhasil diperbarui.');
    }
}
