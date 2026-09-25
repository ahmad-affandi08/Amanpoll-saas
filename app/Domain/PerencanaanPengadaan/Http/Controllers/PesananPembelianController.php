<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Controllers;

use App\Domain\PerencanaanPengadaan\Application\Actions\KelolaPesananPembelian;
use App\Domain\PerencanaanPengadaan\Domain\Enums\StatusPenawaranPenyedia;
use App\Domain\PerencanaanPengadaan\Domain\Enums\StatusPesananPembelian;
use App\Domain\PerencanaanPengadaan\Http\Requests\SimpanPenerimaanPembelianRequest;
use App\Domain\PerencanaanPengadaan\Http\Requests\SimpanPesananPembelianRequest;
use App\Domain\PerencanaanPengadaan\Http\Requests\SimpanTagihanPenyediaRequest;
use App\Domain\PerencanaanPengadaan\Http\Resources\PenawaranPenyediaResource;
use App\Domain\PerencanaanPengadaan\Http\Resources\PesananPembelianResource;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PenawaranPenyedia;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PesananPembelian;
use App\Domain\Persediaan\Domain\Enums\StatusGudang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\Gudang;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Ekspor\EksporDaftar;
use App\Shared\Infrastructure\Ekspor\KolomEkspor;
use App\Shared\Infrastructure\Persistence\BacaRelasi;
use App\Shared\Infrastructure\Persistence\BatasDaftar;
use App\Shared\Infrastructure\Validasi\AturanWajib;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class PesananPembelianController extends Controller
{
    /**
     * @param  array<string, mixed>  $filter
     * @return Builder<PesananPembelian>
     */
    private function kueriTersaring(array $filter): Builder
    {
        return PesananPembelian::query()
            ->with('penyedia')
            ->withCount('penerimaan')
            ->when($filter['cari'] ?? null, fn ($query, $cari) => $query->where('Nomor', 'like', "%{$cari}%"))
            ->when($filter['status'] ?? null, fn ($query, $status) => $query->where('Status', $status))
            ->latest('DibuatPada')
            ->orderBy('Id');
    }

    public function ekspor(Request $request, EksporDaftar $ekspor): StreamedResponse
    {
        $this->authorize('viewAny', PesananPembelian::class);

        $filter = $request->validate([
            'cari' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', Rule::enum(StatusPesananPembelian::class)],
        ]);

        return $ekspor->unduh(
            $this->kueriTersaring($filter),
            [
                KolomEkspor::atribut('Nomor', 'Nomor'),
                KolomEkspor::dari('Penyedia', fn (PesananPembelian $p): string => BacaRelasi::teks(BacaRelasi::model($p, 'penyedia'), 'Nama')),
                KolomEkspor::tanggal('Tanggal Pesanan', 'TanggalPesanan'),
                KolomEkspor::tanggal('Rencana Kirim', 'TanggalKirimRencana'),
                KolomEkspor::atribut('Subtotal', 'Subtotal'),
                KolomEkspor::atribut('Pajak', 'Pajak'),
                KolomEkspor::atribut('Diskon', 'Diskon'),
                KolomEkspor::atribut('Total', 'Total'),
                KolomEkspor::atribut('Mata Uang', 'MataUang'),
                KolomEkspor::atribut('Status', 'Status'),
                KolomEkspor::atribut('Jumlah Penerimaan', 'penerimaan_count'),
            ],
            'daftar-pesanan-pembelian',
            EksporDaftar::formatDari($request),
        );
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', PesananPembelian::class);
        $filter = $request->validate([
            'cari' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', Rule::enum(StatusPesananPembelian::class)],
        ]);

        $pesanan = $this->kueriTersaring($filter)
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('PesananPembelian/Index', [
            'wajib' => ['pesanan' => AturanWajib::untuk(SimpanPesananPembelianRequest::class)],
            'pesanan' => PesananPembelianResource::collection($pesanan),
            'penawaranTerpilih' => PenawaranPenyediaResource::collection(
                PenawaranPenyedia::query()
                    ->where('Status', StatusPenawaranPenyedia::Terpilih->value)
                    ->whereDoesntHave('pesananPembelian')
                    ->with(['penyedia', 'permintaanPenawaran'])
                    ->orderByDesc('DibuatPada')
                    ->limit(BatasDaftar::MAKS)
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
            'wajib' => ['penerimaan' => AturanWajib::untuk(SimpanPenerimaanPembelianRequest::class), 'tagihan' => AturanWajib::untuk(SimpanTagihanPenyediaRequest::class)],
            'pesanan' => new PesananPembelianResource($pesananPembelian),
            // Petugas gudang membuka halaman ini hanya untuk mencatat barang datang.
            'bolehKelola' => Gate::allows('update', $pesananPembelian),
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
