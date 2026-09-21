<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Http\Controllers;

use App\Domain\Persediaan\Application\Actions\BuatReservasiSukuCadang;
use App\Domain\Persediaan\Application\Actions\KonsumsiReservasiSukuCadang;
use App\Domain\Persediaan\Application\Actions\LepaskanReservasiSukuCadang;
use App\Domain\Persediaan\Domain\Enums\StatusSukuCadang;
use App\Domain\Persediaan\Http\Requests\SimpanReservasiSukuCadangRequest;
use App\Domain\Persediaan\Http\Resources\ReservasiSukuCadangResource;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\Gudang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\ReservasiSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\SukuCadang;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ReservasiSukuCadangController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ReservasiSukuCadang::class);

        $filter = $request->validate(['status' => ['nullable', 'string']]);

        $reservasi = ReservasiSukuCadang::query()
            ->with(['gudang', 'sukuCadang', 'dibuatOleh'])
            ->when($filter['status'] ?? null, fn ($q, $v) => $q->where('Status', $v))
            ->latest('DibuatPada')
            ->get();

        return Inertia::render('ReservasiSukuCadang/Index', [
            'reservasi' => ReservasiSukuCadangResource::collection($reservasi),
            'gudang' => Gudang::query()->orderBy('Nama')->get(['Id', 'Nama']),
            'sukuCadang' => SukuCadang::query()->where('Status', StatusSukuCadang::Aktif->value)->orderBy('Nama')->get(['Id', 'Nama', 'Kode']),
            'filter' => $filter,
        ]);
    }

    public function store(SimpanReservasiSukuCadangRequest $request, BuatReservasiSukuCadang $aksi): RedirectResponse
    {
        $this->authorize('create', ReservasiSukuCadang::class);

        $aksi->jalankan($request->validated(), $request->user('web')->Id);

        return back()->with('sukses', 'Reservasi suku cadang berhasil dibuat.');
    }

    public function lepaskan(ReservasiSukuCadang $reservasiSukuCadang, LepaskanReservasiSukuCadang $aksi): RedirectResponse
    {
        $this->authorize('update', $reservasiSukuCadang);

        $aksi->jalankan($reservasiSukuCadang);

        return back()->with('sukses', 'Reservasi suku cadang berhasil dilepas.');
    }

    public function konsumsi(ReservasiSukuCadang $reservasiSukuCadang, KonsumsiReservasiSukuCadang $aksi, Request $request): RedirectResponse
    {
        $this->authorize('update', $reservasiSukuCadang);

        $aksi->jalankan($reservasiSukuCadang, $request->user('web')->Id);

        return back()->with('sukses', 'Reservasi suku cadang berhasil dipakai.');
    }
}
