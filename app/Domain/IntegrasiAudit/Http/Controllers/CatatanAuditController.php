<?php

declare(strict_types=1);

namespace App\Domain\IntegrasiAudit\Http\Controllers;

use App\Domain\IntegrasiAudit\Http\Resources\CatatanAuditResource;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\CatatanAudit;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class CatatanAuditController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', CatatanAudit::class);

        $filter = $request->validate([
            'jenisEntitas' => ['nullable', 'string'],
            'entitasId' => ['nullable', 'string'],
            'penggunaId' => ['nullable', 'string'],
            'aksi' => ['nullable', 'string'],
            'dariTanggal' => ['nullable', 'date'],
            'sampaiTanggal' => ['nullable', 'date'],
        ]);

        $catatan = CatatanAudit::query()
            ->with('pengguna')
            ->when($filter['jenisEntitas'] ?? null, fn ($q, $v) => $q->where('JenisEntitas', $v))
            ->when($filter['entitasId'] ?? null, fn ($q, $v) => $q->where('EntitasId', $v))
            ->when($filter['penggunaId'] ?? null, fn ($q, $v) => $q->where('PenggunaId', $v))
            ->when($filter['aksi'] ?? null, fn ($q, $v) => $q->where('Aksi', 'like', "%{$v}%"))
            ->when($filter['dariTanggal'] ?? null, fn ($q, $v) => $q->whereDate('DibuatPada', '>=', $v))
            ->when($filter['sampaiTanggal'] ?? null, fn ($q, $v) => $q->whereDate('DibuatPada', '<=', $v))
            ->orderByDesc('DibuatPada')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Audit/Index', [
            'catatan' => CatatanAuditResource::collection($catatan),
            'filter' => $filter,
            'jenisEntitasTersedia' => CatatanAudit::query()->distinct()->orderBy('JenisEntitas')->pluck('JenisEntitas'),
        ]);
    }
}
