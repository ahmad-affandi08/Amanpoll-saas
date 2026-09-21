<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Controllers;

use App\Domain\PerencanaanPengadaan\Application\Actions\KelolaPesananPembelian;
use App\Domain\PerencanaanPengadaan\Domain\Enums\StatusPenawaranPenyedia;
use App\Domain\PerencanaanPengadaan\Domain\Enums\StatusPesananPembelian;
use App\Domain\PerencanaanPengadaan\Http\Requests\SimpanPesananPembelianRequest;
use App\Domain\PerencanaanPengadaan\Http\Resources\PenawaranPenyediaResource;
use App\Domain\PerencanaanPengadaan\Http\Resources\PesananPembelianResource;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PenawaranPenyedia;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PesananPembelian;
use App\Domain\Persediaan\Domain\Enums\StatusGudang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\Gudang;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class PesananPembelianController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', PesananPembelian::class);
        $filter = $request->validate([
            'cari' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', Rule::enum(StatusPesananPembelian::class)],
        ]);

        $pesanan = PesananPembelian::query()
            ->with('penyedia')
            ->withCount('penerimaan')
            ->when($filter['cari'] ?? null, fn ($query, $cari) => $query->where('Nomor', 'like', "%{$cari}%"))
            ->when($filter['status'] ?? null, fn ($query, $status) => $query->where('Status', $status))
            ->latest('DibuatPada')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('PesananPembelian/Index', [
            'pesanan' => PesananPembelianResource::collection($pesanan),
            'penawaranTerpilih' => PenawaranPenyediaResource::collection(
                PenawaranPenyedia::query()
                    ->where('Status', StatusPenawaranPenyedia::Terpilih->value)
                    ->whereDoesntHave('pesananPembelian')
                    ->with(['penyedia', 'permintaanPenawaran'])
                    ->orderByDesc('DibuatPada')
                    ->get()
            ),
            'filter' => $filter,
        ]);
    }

    public function store(SimpanPesananPembelianRequest $request, PenawaranPenyedia $penawaranPenyedia, KelolaPesananPembelian $aksi): RedirectResponse
    {
        $this->authorize('create', PesananPembelian::class);
        $pesanan = $aksi->buatDariPenawaran($penawaranPenyedia, $request->validated(), $request->user('web')->Id);

        return redirect()
            ->route('perencanaanPengadaan.po.show', $pesanan)
            ->with('sukses', 'PO dibuat dari penawaran terpilih.');
    }

    public function show(PesananPembelian $pesananPembelian): Response
    {
        $this->authorize('view', $pesananPembelian);
        $pesananPembelian->load([
            'penyedia',
            'permintaanPembelian',
            'posAnggaran',
            'dibuatOleh',
            'detail.sukuCadang',
            'penerimaan.gudang',
            'penerimaan.diterimaOleh',
            'penerimaan.detail.detailPesananPembelian',
            'tagihan.pembayaran',
        ]);

        return Inertia::render('PesananPembelian/Show', [
            'pesanan' => new PesananPembelianResource($pesananPembelian),
            'gudang' => Gudang::query()->where('Status', StatusGudang::Aktif->value)->orderBy('Nama')->get(['Id', 'Kode', 'Nama']),
        ]);
    }

    public function ajukan(Request $request, PesananPembelian $pesananPembelian, KelolaPesananPembelian $aksi): RedirectResponse
    {
        $this->authorize('update', $pesananPembelian);
        $aksi->ajukan($pesananPembelian, $request->user('web')->Id);

        return back()->with('sukses', 'PO diajukan untuk persetujuan.');
    }

    public function kirim(PesananPembelian $pesananPembelian, KelolaPesananPembelian $aksi): RedirectResponse
    {
        $this->authorize('update', $pesananPembelian);
        $aksi->kirim($pesananPembelian);

        return back()->with('sukses', 'PO dikirim dan komitmen anggaran dicatat.');
    }
}
