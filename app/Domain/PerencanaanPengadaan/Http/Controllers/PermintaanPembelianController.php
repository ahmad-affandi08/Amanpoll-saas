<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Controllers;

use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\PerencanaanPengadaan\Application\Actions\KelolaPermintaanPembelian;
use App\Domain\PerencanaanPengadaan\Http\Requests\SimpanDetailPermintaanPembelianRequest;
use App\Domain\PerencanaanPengadaan\Http\Requests\SimpanPermintaanPembelianRequest;
use App\Domain\PerencanaanPengadaan\Http\Resources\PermintaanPembelianResource;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\Anggaran;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\DetailPermintaanPembelian;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PermintaanPembelian;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PosAnggaran;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\RencanaPengadaan;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\SukuCadang;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class PermintaanPembelianController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', PermintaanPembelian::class);
        $filter = $request->validate([
            'cari' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', Rule::in([
                PermintaanPembelian::STATUS_DRAFT,
                PermintaanPembelian::STATUS_MENUNGGU_PERSETUJUAN,
                PermintaanPembelian::STATUS_DISETUJUI,
                PermintaanPembelian::STATUS_DITOLAK,
            ])],
        ]);

        $permintaan = PermintaanPembelian::query()
            ->with(['unitOrganisasi', 'posAnggaran'])
            ->withCount('detail')
            ->when($filter['cari'] ?? null, fn ($query, $cari) => $query->where('Nomor', 'like', "%{$cari}%"))
            ->when($filter['status'] ?? null, fn ($query, $status) => $query->where('Status', $status))
            ->latest('DibuatPada')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('PermintaanPembelian/Index', [
            'permintaan' => PermintaanPembelianResource::collection($permintaan),
            'unitOrganisasi' => UnitOrganisasi::query()->where('Status', 'Aktif')->orderBy('Nama')->get(['Id', 'Nama']),
            'rencana' => RencanaPengadaan::query()
                ->where('Status', RencanaPengadaan::STATUS_DIRENCANAKAN)
                ->orderBy('Nomor')
                ->get(['Id', 'Nomor', 'Nama', 'PosAnggaranId']),
            'posAnggaran' => $this->daftarPosAktif(),
            'filter' => $filter,
        ]);
    }

    public function store(SimpanPermintaanPembelianRequest $request, KelolaPermintaanPembelian $aksi): RedirectResponse
    {
        $this->authorize('create', PermintaanPembelian::class);
        $permintaan = $aksi->buat($request->validated(), $request->user()->Id);

        return redirect()
            ->route('perencanaanPengadaan.permintaan.show', $permintaan)
            ->with('sukses', 'Draft permintaan pembelian dibuat.');
    }

    public function show(PermintaanPembelian $permintaanPembelian): Response
    {
        $this->authorize('view', $permintaanPembelian);
        $permintaanPembelian->load([
            'unitOrganisasi',
            'posAnggaran',
            'rencanaPengadaan',
            'dimintaOleh',
            'detail.asetReferensi',
            'detail.sukuCadang',
        ]);

        return Inertia::render('PermintaanPembelian/Show', [
            'permintaan' => new PermintaanPembelianResource($permintaanPembelian),
            'aset' => Aset::query()->where('Status', Aset::STATUS_AKTIF)->orderBy('Nama')->get(['Id', 'KodeAset', 'Nama']),
            'sukuCadang' => SukuCadang::query()->where('Status', SukuCadang::STATUS_AKTIF)->orderBy('Nama')->get(['Id', 'Kode', 'Nama']),
        ]);
    }

    public function storeDetail(SimpanDetailPermintaanPembelianRequest $request, PermintaanPembelian $permintaanPembelian, KelolaPermintaanPembelian $aksi): RedirectResponse
    {
        $this->authorize('update', $permintaanPembelian);
        $aksi->tambahDetail($permintaanPembelian, $request->validated());

        return back()->with('sukses', 'Item ditambahkan dan total dihitung ulang.');
    }

    public function destroyDetail(PermintaanPembelian $permintaanPembelian, DetailPermintaanPembelian $detailPermintaanPembelian, KelolaPermintaanPembelian $aksi): RedirectResponse
    {
        $this->authorize('update', $permintaanPembelian);
        $aksi->hapusDetail($permintaanPembelian, $detailPermintaanPembelian);

        return back()->with('sukses', 'Item dihapus.');
    }

    public function submit(Request $request, PermintaanPembelian $permintaanPembelian, KelolaPermintaanPembelian $aksi): RedirectResponse
    {
        $this->authorize('update', $permintaanPembelian);
        $aksi->submit($permintaanPembelian, $request->user()->Id);

        return back()->with('sukses', 'Permintaan pembelian diajukan untuk persetujuan.');
    }

    /**
     * Pos anggaran yang boleh dipakai hanya milik anggaran berstatus aktif.
     *
     * @return Collection<int, PosAnggaran>
     */
    private function daftarPosAktif(): Collection
    {
        return PosAnggaran::query()
            ->whereHas('anggaran', fn ($query) => $query->where('Status', Anggaran::STATUS_AKTIF))
            ->orderBy('Kode')
            ->get(['Id', 'Kode', 'Nama']);
    }
}
