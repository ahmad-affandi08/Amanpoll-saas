<?php

declare(strict_types=1);

namespace App\Domain\Penyedia\Http\Controllers;

use App\Domain\Penyedia\Application\Actions\BuatPenilaianPenyedia;
use App\Domain\Penyedia\Http\Requests\SimpanPenilaianPenyediaRequest;
use App\Domain\Penyedia\Http\Resources\PenilaianPenyediaResource;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\PenilaianPenyedia;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

final class PenilaianPenyediaController extends Controller
{
    public function index(Penyedia $penyedia): JsonResponse
    {
        $this->authorize('viewAny', PenilaianPenyedia::class);

        $histori = $penyedia->penilaianPenyedia()->with('dinilaiOleh')->get();

        return response()->json([
            'histori' => PenilaianPenyediaResource::collection($histori),
            'rekap' => [
                'SkorTotalRataRata' => $histori->isEmpty() ? null : round((float) $histori->avg('SkorTotal'), 2),
                'JumlahPenilaian' => $histori->count(),
            ],
        ]);
    }

    public function store(SimpanPenilaianPenyediaRequest $request, Penyedia $penyedia, BuatPenilaianPenyedia $aksi): RedirectResponse
    {
        $this->authorize('create', PenilaianPenyedia::class);

        $aksi->jalankan($penyedia, $request->validated(), $request->user()->Id);

        return back()->with('sukses', 'Penilaian penyedia berhasil disimpan.');
    }
}
