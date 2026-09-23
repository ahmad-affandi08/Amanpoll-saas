<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Controllers;

use App\Domain\PerencanaanPengadaan\Application\Actions\CatatPembayaranPenyedia;
use App\Domain\PerencanaanPengadaan\Application\Actions\KelolaTagihanPenyedia;
use App\Domain\PerencanaanPengadaan\Domain\Enums\StatusTagihanPenyedia;
use App\Domain\PerencanaanPengadaan\Http\Requests\SimpanPembayaranPenyediaRequest;
use App\Domain\PerencanaanPengadaan\Http\Requests\SimpanTagihanPenyediaRequest;
use App\Domain\PerencanaanPengadaan\Http\Resources\TagihanPenyediaResource;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PesananPembelian;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\TagihanPenyedia;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Ekspor\EksporDaftar;
use App\Shared\Infrastructure\Ekspor\KolomEkspor;
use App\Shared\Infrastructure\Persistence\BacaRelasi;
use App\Shared\Infrastructure\Validasi\AturanWajib;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class TagihanPenyediaController extends Controller
{
    /**
     * @param  array<string, mixed>  $filter
     * @return Builder<TagihanPenyedia>
     */
    private function kueriTersaring(array $filter): Builder
    {
        return TagihanPenyedia::query()
            ->with(['penyedia', 'pesananPembelian'])
            ->withCount('pembayaran')
            ->when($filter['cari'] ?? null, fn ($query, $cari) => $query->where('NomorTagihan', 'like', "%{$cari}%"))
            ->when($filter['status'] ?? null, fn ($query, $status) => $query->where('Status', $status))
            ->latest('DibuatPada')
            ->orderBy('Id');
    }

    public function ekspor(Request $request, EksporDaftar $ekspor): StreamedResponse
    {
        $this->authorize('viewAny', TagihanPenyedia::class);

        $filter = $request->validate([
            'cari' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', Rule::enum(StatusTagihanPenyedia::class)],
        ]);

        return $ekspor->unduh(
            $this->kueriTersaring($filter),
            [
                KolomEkspor::atribut('Nomor Tagihan', 'NomorTagihan'),
                KolomEkspor::dari('Penyedia', fn (TagihanPenyedia $t): string => BacaRelasi::teks(BacaRelasi::model($t, 'penyedia'), 'Nama')),
                KolomEkspor::dari('Nomor Pesanan', fn (TagihanPenyedia $t): string => BacaRelasi::teks(BacaRelasi::model($t, 'pesananPembelian'), 'Nomor')),
                KolomEkspor::tanggal('Tanggal Tagihan', 'TanggalTagihan'),
                KolomEkspor::tanggal('Jatuh Tempo', 'JatuhTempo'),
                KolomEkspor::atribut('Subtotal', 'Subtotal'),
                KolomEkspor::atribut('Pajak', 'Pajak'),
                KolomEkspor::atribut('Total', 'Total'),
                KolomEkspor::atribut('Sisa', 'Sisa'),
                KolomEkspor::atribut('Status', 'Status'),
                KolomEkspor::atribut('Jumlah Pembayaran', 'pembayaran_count'),
            ],
            'daftar-tagihan-penyedia',
            EksporDaftar::formatDari($request),
        );
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', TagihanPenyedia::class);
        $filter = $request->validate([
            'cari' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', Rule::enum(StatusTagihanPenyedia::class)],
        ]);

        $tagihan = $this->kueriTersaring($filter)
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('TagihanPenyedia/Index', [
            'tagihan' => TagihanPenyediaResource::collection($tagihan),
            'filter' => $filter,
        ]);
    }

    public function store(SimpanTagihanPenyediaRequest $request, PesananPembelian $pesananPembelian, KelolaTagihanPenyedia $aksi): RedirectResponse
    {
        $this->authorize('create', TagihanPenyedia::class);
        $tagihan = $aksi->buat($pesananPembelian, $request->validated());

        return redirect()
            ->route('perencanaanPengadaan.tagihan.show', $tagihan)
            ->with('sukses', 'Tagihan lolos matching PO/penerimaan.');
    }

    public function show(TagihanPenyedia $tagihanPenyedia): Response
    {
        $this->authorize('view', $tagihanPenyedia);
        $tagihanPenyedia->load(['penyedia', 'pesananPembelian', 'pembayaran.dibuatOleh']);

        return Inertia::render('TagihanPenyedia/Show', [
            'wajib' => ['pembayaran' => AturanWajib::untuk(SimpanPembayaranPenyediaRequest::class)],
            'tagihan' => new TagihanPenyediaResource($tagihanPenyedia),
        ]);
    }

    public function bayar(SimpanPembayaranPenyediaRequest $request, TagihanPenyedia $tagihanPenyedia, CatatPembayaranPenyedia $aksi): RedirectResponse
    {
        $this->authorize('update', $tagihanPenyedia);
        $aksi->jalankan($tagihanPenyedia, $request->validated(), $request->user('web')->Id);

        return back()->with('sukses', 'Pembayaran dicatat dan sisa tagihan dihitung ulang.');
    }
}
