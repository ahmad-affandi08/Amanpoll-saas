<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Controllers;

use App\Domain\PerencanaanPengadaan\Application\Actions\KelolaRencanaPengadaan;
use App\Domain\PerencanaanPengadaan\Domain\Enums\StatusAnggaran;
use App\Domain\PerencanaanPengadaan\Domain\Enums\StatusRencanaPengadaan;
use App\Domain\PerencanaanPengadaan\Domain\Enums\StatusUsulanAset;
use App\Domain\PerencanaanPengadaan\Http\Requests\SimpanDetailRencanaPengadaanRequest;
use App\Domain\PerencanaanPengadaan\Http\Requests\SimpanRencanaPengadaanRequest;
use App\Domain\PerencanaanPengadaan\Http\Resources\RencanaPengadaanResource;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\DetailRencanaPengadaan;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PosAnggaran;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\RencanaPengadaan;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\UsulanAset;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\SukuCadang;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class RencanaPengadaanController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', RencanaPengadaan::class);
        $filter = $request->validate([
            'cari' => ['nullable', 'string', 'max:100'],
            'tahun' => ['nullable', 'integer', 'between:2000,2100'],
            'status' => ['nullable', 'string', Rule::enum(StatusRencanaPengadaan::class)],
        ]);

        $rencana = RencanaPengadaan::query()
            ->with(['posAnggaran', 'dibuatOleh'])
            ->when($filter['cari'] ?? null, fn ($query, $cari) => $query->where(fn ($sub) => $sub
                ->where('Nomor', 'like', "%{$cari}%")
                ->orWhere('Nama', 'like', "%{$cari}%")))
            ->when($filter['tahun'] ?? null, fn ($query, $tahun) => $query->where('Tahun', $tahun))
            ->when($filter['status'] ?? null, fn ($query, $status) => $query->where('Status', $status))
            ->orderByDesc('Tahun')
            ->orderByDesc('DibuatPada')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('RencanaPengadaan/Index', [
            'rencana' => RencanaPengadaanResource::collection($rencana),
            'posAnggaran' => $this->daftarPosAktif(),
            'usulanDisetujui' => UsulanAset::query()
                ->where('Status', StatusUsulanAset::Disetujui->value)
                ->whereDoesntHave('detailRencanaPengadaan')
                ->orderBy('Nomor')
                ->get(['Id', 'Nomor', 'NamaKebutuhan', 'Jumlah', 'EstimasiHargaSatuan']),
            'filter' => $filter,
        ]);
    }

    public function show(RencanaPengadaan $rencanaPengadaan): Response
    {
        $this->authorize('view', $rencanaPengadaan);
        $rencanaPengadaan->load(['posAnggaran.anggaran', 'dibuatOleh', 'detail.usulanAset', 'detail.sukuCadang']);

        return Inertia::render('RencanaPengadaan/Show', [
            'rencana' => new RencanaPengadaanResource($rencanaPengadaan),
            'posAnggaran' => $this->daftarPosAktif(),
            'usulanDisetujui' => UsulanAset::query()
                ->where('Status', StatusUsulanAset::Disetujui->value)
                ->whereDoesntHave('detailRencanaPengadaan')
                ->orderBy('Nomor')
                ->get(['Id', 'Nomor', 'NamaKebutuhan', 'Jumlah', 'EstimasiHargaSatuan']),
            'sukuCadang' => SukuCadang::query()->where('Status', 'Aktif')->orderBy('Nama')->get(['Id', 'Kode', 'Nama']),
        ]);
    }

    public function store(SimpanRencanaPengadaanRequest $request, KelolaRencanaPengadaan $aksi): RedirectResponse
    {
        $this->authorize('create', RencanaPengadaan::class);
        $rencana = $aksi->buat($request->validated(), $request->user('web')->Id);

        return redirect()->route('perencanaanPengadaan.rencana.show', $rencana)->with('sukses', 'Draft rencana pengadaan dibuat.');
    }

    public function update(
        SimpanRencanaPengadaanRequest $request,
        RencanaPengadaan $rencanaPengadaan,
        KelolaRencanaPengadaan $aksi,
    ): RedirectResponse {
        $this->authorize('update', $rencanaPengadaan);
        $aksi->perbarui($rencanaPengadaan, $request->validated());

        return back()->with('sukses', 'Rencana pengadaan diperbarui.');
    }

    public function destroy(RencanaPengadaan $rencanaPengadaan, KelolaRencanaPengadaan $aksi): RedirectResponse
    {
        $this->authorize('delete', $rencanaPengadaan);
        $aksi->hapus($rencanaPengadaan);

        return redirect()->route('perencanaanPengadaan.rencana.index')->with('sukses', 'Rencana pengadaan dihapus.');
    }

    public function storeDetail(
        SimpanDetailRencanaPengadaanRequest $request,
        RencanaPengadaan $rencanaPengadaan,
        KelolaRencanaPengadaan $aksi,
    ): RedirectResponse {
        $this->authorize('update', $rencanaPengadaan);
        $aksi->tambahDetail($rencanaPengadaan, $request->validated());

        return back()->with('sukses', 'Detail rencana ditambahkan dan total dihitung ulang.');
    }

    public function destroyDetail(
        RencanaPengadaan $rencanaPengadaan,
        DetailRencanaPengadaan $detailRencanaPengadaan,
        KelolaRencanaPengadaan $aksi,
    ): RedirectResponse {
        $this->authorize('update', $rencanaPengadaan);
        $aksi->hapusDetail($rencanaPengadaan, $detailRencanaPengadaan);

        return back()->with('sukses', 'Detail rencana dihapus dan total dihitung ulang.');
    }

    public function finalisasi(RencanaPengadaan $rencanaPengadaan, KelolaRencanaPengadaan $aksi): RedirectResponse
    {
        $this->authorize('update', $rencanaPengadaan);
        $aksi->finalisasi($rencanaPengadaan);

        return back()->with('sukses', 'Rencana pengadaan difinalisasi.');
    }

    /**
     * @return Collection<int, PosAnggaran>
     */
    private function daftarPosAktif(): Collection
    {
        return PosAnggaran::query()
            ->whereHas('anggaran', fn ($query) => $query->where('Status', StatusAnggaran::Aktif->value))
            ->with('anggaran')
            ->orderBy('Kode')
            ->get()
            ->each(fn (PosAnggaran $pos) => $pos->setAttribute('Label', "{$pos->Kode} — {$pos->Nama} ({$pos->anggaran->Tahun})"));
    }
}
