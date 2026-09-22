<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Controllers;

use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\ModelAset;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\CatatanAudit;
use App\Domain\PerencanaanPengadaan\Application\Actions\KelolaUsulanAset;
use App\Domain\PerencanaanPengadaan\Domain\Enums\PrioritasUsulanAset;
use App\Domain\PerencanaanPengadaan\Domain\Enums\StatusUsulanAset;
use App\Domain\PerencanaanPengadaan\Http\Requests\SimpanPenilaianUsulanAsetRequest;
use App\Domain\PerencanaanPengadaan\Http\Requests\SimpanUsulanAsetRequest;
use App\Domain\PerencanaanPengadaan\Http\Resources\UsulanAsetResource;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\UsulanAset;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\KeputusanPersetujuan;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\PermintaanPersetujuan;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Persistence\BatasDaftar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class UsulanAsetController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', UsulanAset::class);
        $filter = $request->validate([
            'cari' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', Rule::enum(StatusUsulanAset::class)],
            'prioritas' => ['nullable', 'string', Rule::enum(PrioritasUsulanAset::class)],
        ]);

        $usulan = UsulanAset::query()
            ->with(['unitOrganisasi', 'kategoriAset', 'modelAset', 'diajukanOleh'])
            ->when($filter['cari'] ?? null, fn ($query, $cari) => $query->where(fn ($sub) => $sub
                ->where('Nomor', 'like', "%{$cari}%")
                ->orWhere('NamaKebutuhan', 'like', "%{$cari}%")))
            ->when($filter['status'] ?? null, fn ($query, $status) => $query->where('Status', $status))
            ->when($filter['prioritas'] ?? null, fn ($query, $prioritas) => $query->where('Prioritas', $prioritas))
            ->orderByDesc('DibuatPada')
            ->orderByDesc('Id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('UsulanAset/Index', [
            'usulan' => UsulanAsetResource::collection($usulan),
            'filter' => $filter,
            'unitOrganisasi' => UnitOrganisasi::query()->where('Status', 'Aktif')->orderBy('Nama')->get(['Id', 'Nama']),
            'kategoriAset' => KategoriAset::query()->orderBy('Nama')->get(['Id', 'Nama']),
            'modelAset' => ModelAset::query()->orderBy('Nama')->get(['Id', 'Nama']),
        ]);
    }

    public function show(UsulanAset $usulanAset): Response
    {
        $this->authorize('view', $usulanAset);
        $usulanAset->load([
            'unitOrganisasi',
            'kategoriAset',
            'modelAset',
            'diajukanOleh',
            'penilaian.dinilaiOleh',
        ]);

        $persetujuan = PermintaanPersetujuan::query()
            ->where('JenisEntitas', 'UsulanAset')
            ->where('EntitasId', $usulanAset->Id)
            ->with(['keputusan.penyetuju'])
            ->orderByDesc('DimintaPada')
            ->limit(BatasDaftar::MAKS)
            ->get()
            ->map(fn (PermintaanPersetujuan $item): array => [
                'Id' => $item->Id,
                'Status' => $item->Status,
                'DimintaPada' => $item->DimintaPada->toIso8601String(),
                'SelesaiPada' => $item->getRawOriginal('SelesaiPada') === null ? null : $item->SelesaiPada->toIso8601String(),
                'Keputusan' => $item->keputusan->map(fn (KeputusanPersetujuan $keputusan): array => [
                    'Keputusan' => $keputusan->Keputusan,
                    'Catatan' => $keputusan->Catatan,
                    'NamaPenyetuju' => $keputusan->penyetuju?->Nama,
                    'DiputuskanPada' => $keputusan->DiputuskanPada->toIso8601String(),
                ])->values(),
            ])->values();

        $audit = CatatanAudit::query()
            ->where('JenisEntitas', 'UsulanAset')
            ->where('EntitasId', $usulanAset->Id)
            ->orderByDesc('DibuatPada')
            ->limit(50)
            ->get(['Id', 'Aksi', 'PenggunaId', 'DataSebelum', 'DataSesudah', 'DibuatPada']);

        return Inertia::render('UsulanAset/Show', [
            'usulan' => new UsulanAsetResource($usulanAset),
            'persetujuan' => $persetujuan,
            'audit' => $audit,
            'unitOrganisasi' => UnitOrganisasi::query()->where('Status', 'Aktif')->orderBy('Nama')->get(['Id', 'Nama']),
            'kategoriAset' => KategoriAset::query()->orderBy('Nama')->get(['Id', 'Nama']),
            'modelAset' => ModelAset::query()->orderBy('Nama')->get(['Id', 'Nama']),
        ]);
    }

    public function store(SimpanUsulanAsetRequest $request, KelolaUsulanAset $aksi): RedirectResponse
    {
        $this->authorize('create', UsulanAset::class);
        $usulan = $aksi->buat($request->validated(), $request->user('web')->Id);

        return redirect()->route('perencanaanPengadaan.usulan.show', $usulan)->with('sukses', 'Draft usulan dibuat.');
    }

    public function update(SimpanUsulanAsetRequest $request, UsulanAset $usulanAset, KelolaUsulanAset $aksi): RedirectResponse
    {
        $this->authorize('update', $usulanAset);
        $aksi->perbarui($usulanAset, $request->validated());

        return back()->with('sukses', 'Usulan diperbarui.');
    }

    public function destroy(UsulanAset $usulanAset, KelolaUsulanAset $aksi): RedirectResponse
    {
        $this->authorize('delete', $usulanAset);
        $aksi->hapus($usulanAset);

        return redirect()->route('perencanaanPengadaan.usulan.index')->with('sukses', 'Usulan dihapus.');
    }

    public function submit(UsulanAset $usulanAset, KelolaUsulanAset $aksi): RedirectResponse
    {
        $this->authorize('update', $usulanAset);
        $aksi->submit($usulanAset);

        return back()->with('sukses', 'Usulan disubmit untuk penilaian.');
    }

    public function nilai(
        SimpanPenilaianUsulanAsetRequest $request,
        UsulanAset $usulanAset,
        KelolaUsulanAset $aksi,
    ): RedirectResponse {
        $this->authorize('update', $usulanAset);
        $aksi->nilai($usulanAset, $request->validated(), $request->user('web')->Id);

        return back()->with('sukses', 'Penilaian ditambahkan.');
    }

    public function ajukanPersetujuan(Request $request, UsulanAset $usulanAset, KelolaUsulanAset $aksi): RedirectResponse
    {
        $this->authorize('update', $usulanAset);
        $aksi->ajukanPersetujuan($usulanAset, $request->user('web')->Id);

        return back()->with('sukses', 'Usulan diajukan untuk persetujuan.');
    }
}
