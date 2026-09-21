<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Http\Controllers;

use App\Domain\Persediaan\Application\Actions\BatalkanMutasiStok;
use App\Domain\Persediaan\Application\Actions\BuatMutasiStok;
use App\Domain\Persediaan\Application\Actions\HapusDetailMutasiStok;
use App\Domain\Persediaan\Application\Actions\PostingMutasiStok;
use App\Domain\Persediaan\Application\Actions\TambahDetailMutasiStok;
use App\Domain\Persediaan\Http\Requests\SimpanDetailMutasiStokRequest;
use App\Domain\Persediaan\Http\Requests\SimpanMutasiStokRequest;
use App\Domain\Persediaan\Http\Resources\MutasiStokResource;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\DetailMutasiStok;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\Gudang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\MutasiStok;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\SukuCadang;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class MutasiStokController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', MutasiStok::class);

        $filter = $request->validate(['status' => ['nullable', 'string'], 'jenis' => ['nullable', 'string']]);

        $mutasiStok = MutasiStok::query()
            ->with(['gudangAsal', 'gudangTujuan', 'dibuatOleh'])
            ->when($filter['status'] ?? null, fn ($q, $v) => $q->where('Status', $v))
            ->when($filter['jenis'] ?? null, fn ($q, $v) => $q->where('Jenis', $v))
            ->latest('DibuatPada')
            ->get();

        return Inertia::render('MutasiStok/Index', [
            'mutasiStok' => MutasiStokResource::collection($mutasiStok),
            'gudang' => Gudang::query()->orderBy('Nama')->get(['Id', 'Nama']),
            'filter' => $filter,
        ]);
    }

    public function show(MutasiStok $mutasiStok): Response
    {
        $this->authorize('view', $mutasiStok);

        $mutasiStok->load(['gudangAsal', 'gudangTujuan', 'dibuatOleh', 'detailMutasiStok.sukuCadang', 'detailMutasiStok.kelompokSukuCadang', 'detailMutasiStok.lokasiGudangAsal', 'detailMutasiStok.lokasiGudangTujuan']);

        return Inertia::render('MutasiStok/Show', [
            'mutasiStok' => new MutasiStokResource($mutasiStok),
            'sukuCadang' => SukuCadang::query()->where('Status', SukuCadang::STATUS_AKTIF)->orderBy('Nama')->get(['Id', 'Nama', 'Kode']),
        ]);
    }

    public function store(SimpanMutasiStokRequest $request, BuatMutasiStok $aksi): RedirectResponse
    {
        $this->authorize('create', MutasiStok::class);

        $mutasiStok = $aksi->jalankan($request->validated(), $request->user('web')->Id);

        return redirect("/mutasi-stok/{$mutasiStok->Id}")->with('sukses', 'Draft mutasi stok berhasil dibuat.');
    }

    public function storeDetail(SimpanDetailMutasiStokRequest $request, MutasiStok $mutasiStok, TambahDetailMutasiStok $aksi): RedirectResponse
    {
        $this->authorize('update', $mutasiStok);

        $aksi->jalankan($mutasiStok, $request->validated());

        return back()->with('sukses', 'Baris detail berhasil ditambahkan.');
    }

    public function destroyDetail(DetailMutasiStok $detailMutasiStok, HapusDetailMutasiStok $aksi): RedirectResponse
    {
        /** @var MutasiStok $mutasiStok */
        $mutasiStok = $detailMutasiStok->mutasiStok;
        $this->authorize('update', $mutasiStok);

        $aksi->jalankan($mutasiStok, $detailMutasiStok);

        return back()->with('sukses', 'Baris detail berhasil dihapus.');
    }

    public function posting(MutasiStok $mutasiStok, PostingMutasiStok $aksi, Request $request): RedirectResponse
    {
        $this->authorize('update', $mutasiStok);

        $aksi->jalankan($mutasiStok, $request->user('web')->Id);

        return back()->with('sukses', 'Mutasi stok berhasil diposting.');
    }

    public function batalkan(MutasiStok $mutasiStok, BatalkanMutasiStok $aksi): RedirectResponse
    {
        $this->authorize('update', $mutasiStok);

        $aksi->jalankan($mutasiStok);

        return back()->with('sukses', 'Mutasi stok berhasil dibatalkan.');
    }
}
