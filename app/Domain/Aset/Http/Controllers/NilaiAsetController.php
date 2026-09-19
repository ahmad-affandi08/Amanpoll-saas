<?php

declare(strict_types=1);

namespace App\Domain\Aset\Http\Controllers;

use App\Domain\Aset\Application\Actions\BuatNilaiAset;
use App\Domain\Aset\Application\Services\LayananPenyusutanAset;
use App\Domain\Aset\Http\Requests\SimpanNilaiAsetRequest;
use App\Domain\Aset\Http\Resources\NilaiAsetResource;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;

final class NilaiAsetController extends Controller
{
    public function index(Aset $aset): AnonymousResourceCollection
    {
        $this->authorize('view', $aset);

        return NilaiAsetResource::collection($aset->nilaiAset()->get());
    }

    public function pratinjau(Request $request, Aset $aset, LayananPenyusutanAset $layanan): JsonResponse
    {
        $this->authorize('view', $aset);

        $data = $request->validate(['tanggal' => ['required', 'date']]);

        return response()->json($layanan->hitungGarisLurus($aset, Carbon::parse($data['tanggal'])));
    }

    public function store(SimpanNilaiAsetRequest $request, Aset $aset, BuatNilaiAset $aksi): RedirectResponse
    {
        $this->authorize('update', $aset);

        $aksi->jalankan($aset, $request->validated());

        return back()->with('sukses', 'Nilai aset berhasil dicatat.');
    }
}
