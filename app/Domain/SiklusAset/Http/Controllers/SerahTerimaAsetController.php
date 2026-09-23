<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Http\Controllers;

use App\Domain\Aset\Http\Resources\AsetResource;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Platform\Http\Resources\PenggunaResource;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\SiklusAset\Application\Actions\BuatSerahTerimaAset;
use App\Domain\SiklusAset\Application\Actions\TambahDetailSerahTerimaAset;
use App\Domain\SiklusAset\Application\Actions\TerimaSerahTerimaAset;
use App\Domain\SiklusAset\Http\Requests\SimpanDetailSerahTerimaAsetRequest;
use App\Domain\SiklusAset\Http\Requests\SimpanSerahTerimaAsetRequest;
use App\Domain\SiklusAset\Http\Requests\TerimaSerahTerimaAsetRequest;
use App\Domain\SiklusAset\Http\Resources\SerahTerimaAsetResource;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\SerahTerimaAset;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Ekspor\EksporDaftar;
use App\Shared\Infrastructure\Ekspor\KolomEkspor;
use App\Shared\Infrastructure\Persistence\BacaRelasi;
use App\Shared\Infrastructure\Persistence\BatasDaftar;
use App\Shared\Infrastructure\Validasi\AturanWajib;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class SerahTerimaAsetController extends Controller
{
    /**
     * @param  array<string, mixed>  $filter
     * @return Builder<SerahTerimaAset>
     */
    private function kueriTersaring(array $filter): Builder
    {
        return SerahTerimaAset::query()
            ->with(['pihakMenyerahkan', 'pihakMenerima'])
            ->withCount('detailSerahTerimaAset')
            ->when($filter['status'] ?? null, fn ($q, $v) => $q->where('Status', $v))
            ->latest('DibuatPada')
            ->orderBy('Id');
    }

    public function ekspor(Request $request, EksporDaftar $ekspor): StreamedResponse
    {
        $this->authorize('viewAny', SerahTerimaAset::class);

        $filter = $request->validate(['status' => ['nullable', 'string']]);

        return $ekspor->unduh(
            $this->kueriTersaring($filter),
            [
                KolomEkspor::atribut('Nomor', 'Nomor'),
                KolomEkspor::atribut('Jenis', 'Jenis'),
                KolomEkspor::dari('Pihak Menyerahkan', fn (SerahTerimaAset $s): string => BacaRelasi::teks(BacaRelasi::model($s, 'pihakMenyerahkan'), 'Nama')),
                KolomEkspor::dari('Pihak Menerima', fn (SerahTerimaAset $s): string => BacaRelasi::teks(BacaRelasi::model($s, 'pihakMenerima'), 'Nama')),
                KolomEkspor::atribut('Status', 'Status'),
                KolomEkspor::atribut('Jumlah Aset', 'detail_serah_terima_aset_count'),
                KolomEkspor::tanggal('Diserahkan', 'DiserahkanPada', 'Y-m-d H:i'),
                KolomEkspor::tanggal('Diterima', 'DiterimaPada', 'Y-m-d H:i'),
                KolomEkspor::atribut('Catatan', 'Catatan'),
            ],
            'daftar-serah-terima-aset',
            EksporDaftar::formatDari($request),
        );
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', SerahTerimaAset::class);

        $filter = $request->validate(['status' => ['nullable', 'string']]);

        $serahTerima = $this->kueriTersaring($filter)->paginate(25)->withQueryString();

        return Inertia::render('SerahTerimaAset/Index', [
            'wajib' => ['serahTerima' => AturanWajib::untuk(SimpanSerahTerimaAsetRequest::class)],
            'serahTerima' => SerahTerimaAsetResource::collection($serahTerima),
            'filter' => $filter,
        ]);
    }

    public function show(SerahTerimaAset $serahTerimaAset): Response
    {
        $this->authorize('view', $serahTerimaAset);

        $serahTerimaAset->load(['pihakMenyerahkan', 'pihakMenerima', 'detailSerahTerimaAset.aset']);

        return Inertia::render('SerahTerimaAset/Show', [
            'wajib' => ['detail' => AturanWajib::untuk(SimpanDetailSerahTerimaAsetRequest::class), 'terima' => AturanWajib::untuk(TerimaSerahTerimaAsetRequest::class)],
            'serahTerima' => new SerahTerimaAsetResource($serahTerimaAset),
            'aset' => AsetResource::collection(Aset::query()->orderBy('Nama')->limit(BatasDaftar::MAKS)->get()),
            'pengguna' => PenggunaResource::collection(Pengguna::query()->where('Status', 'Aktif')->orderBy('Nama')->get()),
        ]);
    }

    public function store(SimpanSerahTerimaAsetRequest $request, BuatSerahTerimaAset $aksi): RedirectResponse
    {
        $this->authorize('create', SerahTerimaAset::class);

        $serahTerima = $aksi->jalankan($request->validated());

        return redirect("/serah-terima-aset/{$serahTerima->Id}")->with('sukses', 'Dokumen serah terima berhasil dibuat.');
    }

    public function storeDetail(SimpanDetailSerahTerimaAsetRequest $request, SerahTerimaAset $serahTerimaAset, TambahDetailSerahTerimaAset $aksi): RedirectResponse
    {
        $this->authorize('update', $serahTerimaAset);

        $data = $request->validated();
        $aksi->jalankan($serahTerimaAset, $data['AsetId'], $data['KondisiSaatDiserahkan'] ?? null, $data['Catatan'] ?? null);

        return back()->with('sukses', 'Aset berhasil ditambahkan ke dokumen serah terima.');
    }

    public function terima(TerimaSerahTerimaAsetRequest $request, SerahTerimaAset $serahTerimaAset, TerimaSerahTerimaAset $aksi): RedirectResponse
    {
        $this->authorize('update', $serahTerimaAset);

        $aksi->jalankan($serahTerimaAset, $request->validated()['Detail']);

        return back()->with('sukses', 'Serah terima aset berhasil dikonfirmasi diterima.');
    }
}
