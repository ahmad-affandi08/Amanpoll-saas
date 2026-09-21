<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Controllers;

use App\Domain\Penyedia\Domain\Enums\StatusPenyedia;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia;
use App\Domain\PerencanaanPengadaan\Application\Actions\KelolaPenawaranPenyedia;
use App\Domain\PerencanaanPengadaan\Application\Actions\KelolaPermintaanPenawaran;
use App\Domain\PerencanaanPengadaan\Domain\Enums\StatusPermintaanPembelian;
use App\Domain\PerencanaanPengadaan\Domain\Enums\StatusPermintaanPenawaran;
use App\Domain\PerencanaanPengadaan\Http\Requests\SimpanPenawaranPenyediaRequest;
use App\Domain\PerencanaanPengadaan\Http\Requests\SimpanPermintaanPenawaranRequest;
use App\Domain\PerencanaanPengadaan\Http\Resources\PermintaanPenawaranResource;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PenawaranPenyedia;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PermintaanPembelian;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PermintaanPenawaran;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class PermintaanPenawaranController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', PermintaanPenawaran::class);
        $filter = $request->validate([
            'cari' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', Rule::enum(StatusPermintaanPenawaran::class)],
        ]);

        $rfq = PermintaanPenawaran::query()
            ->with('permintaanPembelian')
            ->withCount(['penyediaDiundang', 'penawaran'])
            ->when($filter['cari'] ?? null, fn ($query, $cari) => $query->where('Nomor', 'like', "%{$cari}%"))
            ->when($filter['status'] ?? null, fn ($query, $status) => $query->where('Status', $status))
            ->latest('DibuatPada')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('PermintaanPenawaran/Index', [
            'rfq' => PermintaanPenawaranResource::collection($rfq),
            'permintaanDisetujui' => PermintaanPembelian::query()
                ->where('Status', StatusPermintaanPembelian::Disetujui->value)
                ->orderBy('Nomor')
                ->get(['Id', 'Nomor', 'TotalEstimasi']),
            'penyedia' => Penyedia::query()->where('Status', StatusPenyedia::Aktif->value)->orderBy('Nama')->get(['Id', 'Kode', 'Nama']),
            'filter' => $filter,
        ]);
    }

    public function store(SimpanPermintaanPenawaranRequest $request, KelolaPermintaanPenawaran $aksi): RedirectResponse
    {
        $this->authorize('create', PermintaanPenawaran::class);
        $data = $request->validated();
        $permintaan = PermintaanPembelian::query()->whereKey($data['PermintaanPembelianId'])->firstOrFail();
        $rfq = $aksi->buat($permintaan, $data, $request->user('web')->Id);

        return redirect()
            ->route('perencanaanPengadaan.rfq.show', $rfq)
            ->with('sukses', 'Draft RFQ dibuat.');
    }

    public function show(PermintaanPenawaran $permintaanPenawaran): Response
    {
        $this->authorize('view', $permintaanPenawaran);
        $permintaanPenawaran->load([
            'permintaanPembelian.detail',
            'dibuatOleh',
            'penyediaDiundang.penyedia',
            'penawaran.penyedia',
            'penawaran.detail',
        ]);

        return Inertia::render('PermintaanPenawaran/Show', [
            'rfq' => new PermintaanPenawaranResource($permintaanPenawaran),
        ]);
    }

    public function buka(PermintaanPenawaran $permintaanPenawaran, KelolaPermintaanPenawaran $aksi): RedirectResponse
    {
        $this->authorize('update', $permintaanPenawaran);
        $aksi->buka($permintaanPenawaran);

        return back()->with('sukses', 'RFQ dibuka dan ditandai terkirim ke penyedia.');
    }

    public function storePenawaran(SimpanPenawaranPenyediaRequest $request, PermintaanPenawaran $permintaanPenawaran, KelolaPenawaranPenyedia $aksi): RedirectResponse
    {
        $this->authorize('update', $permintaanPenawaran);
        $aksi->catat($permintaanPenawaran, $request->validated());

        return back()->with('sukses', 'Penawaran penyedia dicatat dan total diverifikasi server.');
    }

    public function pilih(PermintaanPenawaran $permintaanPenawaran, PenawaranPenyedia $penawaranPenyedia, KelolaPenawaranPenyedia $aksi): RedirectResponse
    {
        $this->authorize('update', $permintaanPenawaran);
        abort_unless($penawaranPenyedia->PermintaanPenawaranId === $permintaanPenawaran->Id, 404);
        $aksi->pilih($penawaranPenyedia);

        return back()->with('sukses', 'Penawaran dipilih dan RFQ ditutup.');
    }
}
