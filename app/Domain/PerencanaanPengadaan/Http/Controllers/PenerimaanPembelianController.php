<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Controllers;

use App\Domain\PerencanaanPengadaan\Application\Actions\CatatPenerimaanPembelian;
use App\Domain\PerencanaanPengadaan\Domain\Enums\StatusPesananPembelian;
use App\Domain\PerencanaanPengadaan\Http\Requests\SimpanPenerimaanPembelianRequest;
use App\Domain\PerencanaanPengadaan\Http\Resources\PenerimaanPembelianResource;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PenerimaanPembelian;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PesananPembelian;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Ekspor\EksporDaftar;
use App\Shared\Infrastructure\Ekspor\KolomEkspor;
use App\Shared\Infrastructure\Persistence\BacaRelasi;
use App\Shared\Infrastructure\Persistence\BatasDaftar;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class PenerimaanPembelianController extends Controller
{
    /**
     * @param  array<string, mixed>  $filter
     * @return Builder<PenerimaanPembelian>
     */
    private function kueriTersaring(array $filter): Builder
    {
        return PenerimaanPembelian::query()
            ->with(['pesananPembelian.penyedia', 'gudang', 'diterimaOleh'])
            ->withCount('detail')
            ->when($filter['cari'] ?? null, fn ($query, $cari) => $query->where(fn ($sub) => $sub
                ->where('Nomor', 'like', "%{$cari}%")
                ->orWhere('NomorSuratJalan', 'like', "%{$cari}%")))
            ->latest('DibuatPada')
            ->orderBy('Id');
    }

    public function ekspor(Request $request, EksporDaftar $ekspor): StreamedResponse
    {
        $this->authorize('viewAny', PenerimaanPembelian::class);

        $filter = $request->validate(['cari' => ['nullable', 'string', 'max:100']]);

        return $ekspor->unduh(
            $this->kueriTersaring($filter),
            [
                KolomEkspor::atribut('Nomor', 'Nomor'),
                KolomEkspor::dari('Nomor Pesanan', fn (PenerimaanPembelian $p): string => BacaRelasi::teks(BacaRelasi::model($p, 'pesananPembelian'), 'Nomor')),
                KolomEkspor::dari('Penyedia', function (PenerimaanPembelian $p): string {
                    $pesanan = BacaRelasi::model($p, 'pesananPembelian');

                    return $pesanan === null ? '' : BacaRelasi::teks(BacaRelasi::model($pesanan, 'penyedia'), 'Nama');
                }),
                KolomEkspor::dari('Gudang', fn (PenerimaanPembelian $p): string => BacaRelasi::teks(BacaRelasi::model($p, 'gudang'), 'Nama')),
                KolomEkspor::tanggal('Tanggal Terima', 'TanggalTerima'),
                KolomEkspor::atribut('Nomor Surat Jalan', 'NomorSuratJalan'),
                KolomEkspor::atribut('Status', 'Status'),
                KolomEkspor::atribut('Jumlah Baris', 'detail_count'),
                KolomEkspor::dari('Diterima Oleh', fn (PenerimaanPembelian $p): string => BacaRelasi::teks(BacaRelasi::model($p, 'diterimaOleh'), 'Nama')),
            ],
            'daftar-penerimaan-pembelian',
            EksporDaftar::formatDari($request),
        );
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', PenerimaanPembelian::class);
        $filter = $request->validate([
            'cari' => ['nullable', 'string', 'max:100'],
        ]);

        $penerimaan = $this->kueriTersaring($filter)
            ->paginate(20)
            ->withQueryString();

        // PO yang sudah dikirim ke penyedia dan barangnya belum lengkap: pintu masuk petugas
        // gudang, yang tidak membuka daftar PO seluruhnya.
        $menungguPenerimaan = PesananPembelian::query()
            ->with('penyedia:Id,Nama')
            ->whereIn('Status', [StatusPesananPembelian::Dikirim->value, StatusPesananPembelian::DiterimaSebagian->value])
            ->orderBy('TanggalKirimRencana')
            ->orderBy('Id')
            ->limit(BatasDaftar::MAKS)
            ->get()
            ->map(fn (PesananPembelian $po): array => [
                'Id' => $po->Id,
                'Nomor' => $po->Nomor,
                'Status' => $po->Status,
                'NamaPenyedia' => BacaRelasi::teks(BacaRelasi::model($po, 'penyedia'), 'Nama'),
                'TanggalKirimRencana' => $po->TanggalKirimRencana?->toDateString(),
            ]);

        return Inertia::render('PenerimaanPembelian/Index', [
            'penerimaan' => PenerimaanPembelianResource::collection($penerimaan),
            'menungguPenerimaan' => $menungguPenerimaan,
            'filter' => $filter,
        ]);
    }

    public function store(SimpanPenerimaanPembelianRequest $request, PesananPembelian $pesananPembelian, CatatPenerimaanPembelian $aksi): RedirectResponse
    {
        $this->authorize('create', PenerimaanPembelian::class);
        $aksi->jalankan($pesananPembelian, $request->validated(), $request->user('web')->Id);

        return back()->with('sukses', 'Penerimaan dicatat; stok/aset dan status PO telah disinkronkan.');
    }
}
