<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Http\Controllers;

use App\Domain\Aset\Http\Resources\AsetResource;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Platform\Http\Resources\LokasiResource;
use App\Domain\Platform\Http\Resources\UnitOrganisasiResource;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use App\Domain\SiklusAset\Application\Actions\BatalkanPermintaanMutasiAset;
use App\Domain\SiklusAset\Application\Actions\BuatPermintaanMutasiAset;
use App\Domain\SiklusAset\Application\Actions\EksekusiMutasiAset;
use App\Domain\SiklusAset\Application\Actions\HapusDetailMutasiAset;
use App\Domain\SiklusAset\Application\Actions\SubmitPermintaanMutasiAset;
use App\Domain\SiklusAset\Application\Actions\TambahDetailMutasiAset;
use App\Domain\SiklusAset\Http\Requests\SimpanDetailMutasiAsetRequest;
use App\Domain\SiklusAset\Http\Requests\SimpanPermintaanMutasiAsetRequest;
use App\Domain\SiklusAset\Http\Resources\PermintaanMutasiAsetResource;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\DetailMutasiAset;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\PermintaanMutasiAset;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PermintaanMutasiAsetController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', PermintaanMutasiAset::class);

        $filter = $request->validate([
            'status' => ['nullable', 'string'],
        ]);

        $permintaan = PermintaanMutasiAset::query()
            ->with(['unitAsal', 'unitTujuan', 'lokasiAsal', 'lokasiTujuan', 'dimintaOleh'])
            ->when($filter['status'] ?? null, fn ($q, $v) => $q->where('Status', $v))
            ->latest('DimintaPada')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('MutasiAset/Index', [
            'permintaan' => PermintaanMutasiAsetResource::collection($permintaan),
            'filter' => $filter,
            'lokasi' => LokasiResource::collection(Lokasi::query()->orderBy('Nama')->get()),
            'unitOrganisasi' => UnitOrganisasiResource::collection(UnitOrganisasi::query()->where('Status', 'Aktif')->orderBy('Nama')->get()),
        ]);
    }

    public function show(PermintaanMutasiAset $permintaanMutasiAset): Response
    {
        $this->authorize('view', $permintaanMutasiAset);

        $permintaanMutasiAset->load(['unitAsal', 'unitTujuan', 'lokasiAsal', 'lokasiTujuan', 'dimintaOleh', 'detailMutasiAset.aset']);

        return Inertia::render('MutasiAset/Show', [
            'permintaan' => new PermintaanMutasiAsetResource($permintaanMutasiAset),
            'aset' => AsetResource::collection(Aset::query()->orderBy('Nama')->get()),
            'unitOrganisasi' => UnitOrganisasiResource::collection(UnitOrganisasi::query()->where('Status', 'Aktif')->orderBy('Nama')->get()),
            'lokasi' => LokasiResource::collection(Lokasi::query()->orderBy('Nama')->get()),
        ]);
    }

    public function store(SimpanPermintaanMutasiAsetRequest $request, BuatPermintaanMutasiAset $aksi): RedirectResponse
    {
        $this->authorize('create', PermintaanMutasiAset::class);

        $permintaan = $aksi->jalankan($request->validated(), $request->user()->Id);

        return redirect("/mutasi-aset/{$permintaan->Id}")->with('sukses', 'Draft permintaan mutasi berhasil dibuat.');
    }

    public function storeDetail(SimpanDetailMutasiAsetRequest $request, PermintaanMutasiAset $permintaanMutasiAset, TambahDetailMutasiAset $aksi): RedirectResponse
    {
        $this->authorize('update', $permintaanMutasiAset);

        $data = $request->validated();
        $aksi->jalankan($permintaanMutasiAset, $data['AsetId'], $data['Catatan'] ?? null);

        return back()->with('sukses', 'Aset berhasil ditambahkan ke daftar mutasi.');
    }

    public function destroyDetail(DetailMutasiAset $detailMutasiAset, HapusDetailMutasiAset $aksi): RedirectResponse
    {
        /** @var PermintaanMutasiAset $permintaan */
        $permintaan = $detailMutasiAset->permintaanMutasiAset;
        $this->authorize('update', $permintaan);

        $aksi->jalankan($permintaan, $detailMutasiAset);

        return back()->with('sukses', 'Aset berhasil dihapus dari daftar mutasi.');
    }

    public function submit(Request $request, PermintaanMutasiAset $permintaanMutasiAset, SubmitPermintaanMutasiAset $aksi): RedirectResponse
    {
        $this->authorize('update', $permintaanMutasiAset);

        $aksi->jalankan($permintaanMutasiAset, $request->user()->Id);

        return back()->with('sukses', 'Permintaan mutasi berhasil disubmit untuk persetujuan.');
    }

    public function batalkan(PermintaanMutasiAset $permintaanMutasiAset, BatalkanPermintaanMutasiAset $aksi): RedirectResponse
    {
        $this->authorize('update', $permintaanMutasiAset);

        $aksi->jalankan($permintaanMutasiAset);

        return back()->with('sukses', 'Permintaan mutasi berhasil dibatalkan.');
    }

    public function eksekusi(Request $request, PermintaanMutasiAset $permintaanMutasiAset, EksekusiMutasiAset $aksi): RedirectResponse
    {
        $this->authorize('update', $permintaanMutasiAset);

        $aksi->jalankan($permintaanMutasiAset, $request->user()->Id);

        return back()->with('sukses', 'Mutasi aset berhasil dieksekusi.');
    }
}
