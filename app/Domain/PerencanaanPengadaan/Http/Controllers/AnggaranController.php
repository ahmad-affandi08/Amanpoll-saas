<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Controllers;

use App\Domain\PerencanaanPengadaan\Application\Actions\CatatTransaksiAnggaran;
use App\Domain\PerencanaanPengadaan\Application\Actions\KelolaAnggaran;
use App\Domain\PerencanaanPengadaan\Application\Actions\KelolaPosAnggaran;
use App\Domain\PerencanaanPengadaan\Http\Requests\SimpanAnggaranRequest;
use App\Domain\PerencanaanPengadaan\Http\Requests\SimpanPosAnggaranRequest;
use App\Domain\PerencanaanPengadaan\Http\Requests\SimpanTransaksiAnggaranRequest;
use App\Domain\PerencanaanPengadaan\Http\Resources\AnggaranResource;
use App\Domain\PerencanaanPengadaan\Http\Resources\TransaksiAnggaranResource;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\Anggaran;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PosAnggaran;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\TransaksiAnggaran;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class AnggaranController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Anggaran::class);
        $filter = $request->validate([
            'cari' => ['nullable', 'string', 'max:100'],
            'tahun' => ['nullable', 'integer', 'between:2000,2100'],
            'status' => ['nullable', 'string', Rule::in([
                Anggaran::STATUS_DRAFT,
                Anggaran::STATUS_MENUNGGU_PERSETUJUAN,
                Anggaran::STATUS_AKTIF,
                Anggaran::STATUS_DITOLAK,
                Anggaran::STATUS_DITUTUP,
            ])],
        ]);

        $anggaran = Anggaran::query()
            ->with('unitOrganisasi')
            ->withCount('posAnggaran')
            ->when($filter['cari'] ?? null, fn ($query, $cari) => $query->where(fn ($sub) => $sub
                ->where('Kode', 'like', "%{$cari}%")
                ->orWhere('Nama', 'like', "%{$cari}%")))
            ->when($filter['tahun'] ?? null, fn ($query, $tahun) => $query->where('Tahun', $tahun))
            ->when($filter['status'] ?? null, fn ($query, $status) => $query->where('Status', $status))
            ->orderByDesc('Tahun')
            ->orderBy('Kode')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Anggaran/Index', [
            'anggaran' => AnggaranResource::collection($anggaran),
            'filter' => $filter,
            'unitOrganisasi' => UnitOrganisasi::query()
                ->where('Status', 'Aktif')
                ->orderBy('Nama')
                ->get(['Id', 'Nama']),
        ]);
    }

    public function show(Anggaran $anggaran): Response
    {
        $this->authorize('view', $anggaran);
        $anggaran->load([
            'unitOrganisasi',
            'posAnggaran' => fn ($query) => $query->with('induk')->orderBy('Kode'),
        ]);

        $transaksi = TransaksiAnggaran::query()
            ->whereIn('PosAnggaranId', $anggaran->posAnggaran->pluck('Id'))
            ->with('posAnggaran')
            ->orderByDesc('Tanggal')
            ->orderByDesc('DibuatPada')
            ->limit(100)
            ->get();

        return Inertia::render('Anggaran/Show', [
            'anggaran' => new AnggaranResource($anggaran),
            'transaksi' => TransaksiAnggaranResource::collection($transaksi),
            'dapatMenyesuaikan' => Gate::allows('adjust', $anggaran),
        ]);
    }

    public function store(SimpanAnggaranRequest $request, KelolaAnggaran $aksi): RedirectResponse
    {
        $this->authorize('create', Anggaran::class);
        $anggaran = $aksi->buat($request->validated());

        return redirect()->route('perencanaanPengadaan.anggaran.show', $anggaran)
            ->with('sukses', 'Draft anggaran dibuat.');
    }

    public function update(SimpanAnggaranRequest $request, Anggaran $anggaran, KelolaAnggaran $aksi): RedirectResponse
    {
        $this->authorize('update', $anggaran);
        $aksi->perbarui($anggaran, $request->validated());

        return back()->with('sukses', 'Anggaran diperbarui.');
    }

    public function destroy(Anggaran $anggaran, KelolaAnggaran $aksi): RedirectResponse
    {
        $this->authorize('delete', $anggaran);
        $aksi->hapus($anggaran);

        return redirect()->route('perencanaanPengadaan.anggaran.index')->with('sukses', 'Anggaran dihapus.');
    }

    public function ajukan(Request $request, Anggaran $anggaran, KelolaAnggaran $aksi): RedirectResponse
    {
        $this->authorize('update', $anggaran);
        $aksi->ajukan($anggaran, $request->user('web')->Id);

        return back()->with('sukses', 'Anggaran diajukan.');
    }

    public function storePos(SimpanPosAnggaranRequest $request, Anggaran $anggaran, KelolaPosAnggaran $aksi): RedirectResponse
    {
        $this->authorize('create', PosAnggaran::class);
        $aksi->buat($anggaran, $request->validated());

        return back()->with('sukses', 'Pos anggaran ditambahkan.');
    }

    public function updatePos(SimpanPosAnggaranRequest $request, PosAnggaran $posAnggaran, KelolaPosAnggaran $aksi): RedirectResponse
    {
        $this->authorize('update', $posAnggaran);
        $aksi->perbarui($posAnggaran, $request->validated());

        return back()->with('sukses', 'Pos anggaran diperbarui.');
    }

    public function destroyPos(PosAnggaran $posAnggaran, KelolaPosAnggaran $aksi): RedirectResponse
    {
        $this->authorize('delete', $posAnggaran);
        $aksi->hapus($posAnggaran);

        return back()->with('sukses', 'Pos anggaran dihapus.');
    }

    public function storeTransaksi(
        SimpanTransaksiAnggaranRequest $request,
        PosAnggaran $posAnggaran,
        CatatTransaksiAnggaran $aksi,
    ): RedirectResponse {
        $this->authorize('transact', $posAnggaran);
        if ($request->string('Jenis')->toString() === TransaksiAnggaran::JENIS_PENYESUAIAN) {
            $this->authorize('adjust', $posAnggaran->anggaran);
        }

        $aksi->jalankan($posAnggaran, $request->validated());

        return back()->with('sukses', 'Transaksi anggaran dicatat dan saldo direkonsiliasi.');
    }
}
