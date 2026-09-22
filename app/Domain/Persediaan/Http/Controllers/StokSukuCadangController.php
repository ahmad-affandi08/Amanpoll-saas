<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Http\Controllers;

use App\Domain\Persediaan\Domain\Enums\StatusSukuCadang;
use App\Domain\Persediaan\Http\Resources\StokSukuCadangResource;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\Gudang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\StokSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\SukuCadang;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Persistence\BatasDaftar;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Hanya baca. */
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
            ->limit(BatasDaftar::MAKS)
            ->get();

        return Inertia::render('StokSukuCadang/Index', [
            'stok' => StokSukuCadangResource::collection($stok),
            'gudang' => Gudang::query()->orderBy('Nama')->get(['Id', 'Nama']),
            'sukuCadang' => SukuCadang::query()->where('Status', StatusSukuCadang::Aktif->value)->orderBy('Nama')->get(['Id', 'Nama', 'Kode']),
            'filter' => $filter,
        ]);
    }
}
