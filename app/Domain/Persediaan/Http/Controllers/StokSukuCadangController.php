<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Http\Controllers;

use App\Domain\Persediaan\Http\Resources\StokSukuCadangResource;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\Gudang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\StokSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\SukuCadang;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Hanya baca. StokSukuCadang TIDAK PERNAH punya endpoint store/update/destroy
 * -- lihat Gate 10 dan ADR 0010.
 */
final class StokSukuCadangController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', StokSukuCadang::class);

        $filter = $request->validate([
            'gudangId' => ['nullable', 'string'],
            'sukuCadangId' => ['nullable', 'string'],
        ]);

        $stok = StokSukuCadang::query()
            ->with(['gudang', 'lokasiGudang', 'sukuCadang', 'kelompokSukuCadang'])
            ->when($filter['gudangId'] ?? null, fn ($q, $v) => $q->where('GudangId', $v))
            ->when($filter['sukuCadangId'] ?? null, fn ($q, $v) => $q->where('SukuCadangId', $v))
            ->get();

        return Inertia::render('StokSukuCadang/Index', [
            'stok' => StokSukuCadangResource::collection($stok),
            'gudang' => Gudang::query()->orderBy('Nama')->get(['Id', 'Nama']),
            'sukuCadang' => SukuCadang::query()->where('Status', SukuCadang::STATUS_AKTIF)->orderBy('Nama')->get(['Id', 'Nama', 'Kode']),
            'filter' => $filter,
        ]);
    }
}
