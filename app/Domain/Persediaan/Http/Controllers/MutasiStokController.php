<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Http\Controllers;

use App\Core\Izin\ScopeLingkup;
use App\Domain\Persediaan\Application\Actions\BatalkanMutasiStok;
use App\Domain\Persediaan\Application\Actions\BuatMutasiStok;
use App\Domain\Persediaan\Application\Actions\HapusDetailMutasiStok;
use App\Domain\Persediaan\Application\Actions\PostingMutasiStok;
use App\Domain\Persediaan\Application\Actions\TambahDetailMutasiStok;
use App\Domain\Persediaan\Application\Services\LingkupGudang;
use App\Domain\Persediaan\Domain\Enums\StatusSukuCadang;
use App\Domain\Persediaan\Http\Requests\SimpanDetailMutasiStokRequest;
use App\Domain\Persediaan\Http\Requests\SimpanMutasiStokRequest;
use App\Domain\Persediaan\Http\Resources\MutasiStokResource;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\DetailMutasiStok;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\Gudang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\MutasiStok;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\SukuCadang;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Ekspor\EksporDaftar;
use App\Shared\Infrastructure\Ekspor\KolomEkspor;
use App\Shared\Infrastructure\Persistence\BacaRelasi;
use App\Shared\Infrastructure\Validasi\AturanWajib;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class MutasiStokController extends Controller
{
    public function __construct(private readonly LingkupGudang $lingkupGudang) {}

    /**
     * Nama kedua gudang dimuat lepas dari ScopeLingkup: mutasi yang terlihat karena
     * satu sisinya tetap menyebut nama gudang di sisi lain, bukan tanda kosong.
     *
     * @return array<string, Closure(Relation<*, *, *>): mixed>
     */
    private static function relasiGudang(): array
    {
        return [
            'gudangAsal' => fn (Relation $relasi) => $relasi->withoutGlobalScope(ScopeLingkup::class),
            'gudangTujuan' => fn (Relation $relasi) => $relasi->withoutGlobalScope(ScopeLingkup::class),
        ];
    }

    /**
     * @param  array<string, mixed>  $filter
     * @return Builder<MutasiStok>
     */
    private function kueriTersaring(array $filter): Builder
    {
        // Mutasi terlihat bila salah satu sisinya di gudang yang terlihat (PRD 8.21); ikut terbawa ke ekspor.
        return $this->lingkupGudang->saring(MutasiStok::query(), 'MutasiStok.GudangAsalId', 'MutasiStok.GudangTujuanId')
            ->with([...self::relasiGudang(), 'dibuatOleh'])
            ->when($filter['status'] ?? null, fn ($q, $v) => $q->where('Status', $v))
            ->when($filter['jenis'] ?? null, fn ($q, $v) => $q->where('Jenis', $v))
            ->latest('DibuatPada')
            ->orderBy('Id');
    }

    public function ekspor(Request $request, EksporDaftar $ekspor): StreamedResponse
    {
        $this->authorize('viewAny', MutasiStok::class);

        $filter = $request->validate(['status' => ['nullable', 'string'], 'jenis' => ['nullable', 'string']]);

        return $ekspor->unduh(
            $this->kueriTersaring($filter),
            [
                KolomEkspor::atribut('Nomor', 'Nomor'),
                KolomEkspor::atribut('Jenis', 'Jenis'),
                KolomEkspor::dari('Gudang Asal', fn (MutasiStok $m): string => BacaRelasi::teks(BacaRelasi::model($m, 'gudangAsal'), 'Nama')),
                KolomEkspor::dari('Gudang Tujuan', fn (MutasiStok $m): string => BacaRelasi::teks(BacaRelasi::model($m, 'gudangTujuan'), 'Nama')),
                KolomEkspor::atribut('Status', 'Status'),
                KolomEkspor::dari('Dibuat Oleh', fn (MutasiStok $m): string => BacaRelasi::teks(BacaRelasi::model($m, 'dibuatOleh'), 'Nama')),
                KolomEkspor::tanggal('Dibuat', 'DibuatPada', 'Y-m-d H:i'),
            ],
            'daftar-mutasi-stok',
            EksporDaftar::formatDari($request),
        );
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', MutasiStok::class);

        $filter = $request->validate(['status' => ['nullable', 'string'], 'jenis' => ['nullable', 'string']]);

        $mutasiStok = $this->kueriTersaring($filter)->paginate(25)->withQueryString();

        return Inertia::render('MutasiStok/Index', [
            'wajib' => ['mutasi' => AturanWajib::untuk(SimpanMutasiStokRequest::class)],
            'mutasiStok' => MutasiStokResource::collection($mutasiStok),
            'gudang' => Gudang::query()->orderBy('Nama')->get(['Id', 'Nama']),
            'filter' => $filter,
        ]);
    }

    public function show(MutasiStok $mutasiStok): Response
    {
        $this->authorize('view', $mutasiStok);

        $mutasiStok->load([...self::relasiGudang(), 'dibuatOleh', 'detailMutasiStok.sukuCadang', 'detailMutasiStok.kelompokSukuCadang', 'detailMutasiStok.lokasiGudangAsal', 'detailMutasiStok.lokasiGudangTujuan']);

        return Inertia::render('MutasiStok/Show', [
            'wajib' => ['detail' => AturanWajib::untuk(SimpanDetailMutasiStokRequest::class)],
            'mutasiStok' => new MutasiStokResource($mutasiStok),
            'sukuCadang' => SukuCadang::query()->where('Status', StatusSukuCadang::Aktif->value)->orderBy('Nama')->get(['Id', 'Nama', 'Kode']),
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
