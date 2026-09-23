<?php

declare(strict_types=1);

namespace App\Domain\Aset\Http\Controllers;

use App\Domain\Aset\Application\Actions\BuatModelAset;
use App\Domain\Aset\Application\Actions\HapusModelAset;
use App\Domain\Aset\Application\Actions\UbahModelAset;
use App\Domain\Aset\Http\Requests\SimpanModelAsetRequest;
use App\Domain\Aset\Http\Resources\KategoriAsetResource;
use App\Domain\Aset\Http\Resources\MerekResource;
use App\Domain\Aset\Http\Resources\ModelAsetResource;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Merek;
use App\Domain\Aset\Infrastructure\Persistence\Models\ModelAset;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Ekspor\EksporDaftar;
use App\Shared\Infrastructure\Ekspor\KolomEkspor;
use App\Shared\Infrastructure\Persistence\BacaRelasi;
use App\Shared\Infrastructure\Persistence\DaftarTersaring;
use App\Shared\Infrastructure\Validasi\AturanWajib;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ModelAsetController extends Controller
{
    /**
     * Penyaring daftar, dipakai bersama halaman dan ekspornya.
     *
     * @return DaftarTersaring<ModelAset>
     */
    private function daftar(Request $request): DaftarTersaring
    {
        return DaftarTersaring::untuk($request, ModelAset::query()->with(['kategoriAset', 'merek']))
            ->cari(['Nama', 'KodeModel'])
            ->urut(['Nama'], bawaan: 'Nama')
            ->faset(['KategoriAsetId', 'MerekId']);
    }

    public function ekspor(Request $request, EksporDaftar $ekspor): StreamedResponse
    {
        $this->authorize('viewAny', ModelAset::class);

        return $ekspor->unduh(
            $this->daftar($request)->kueriTersaring(),
            [
                KolomEkspor::atribut('Kode Model', 'KodeModel'),
                KolomEkspor::atribut('Nama', 'Nama'),
                KolomEkspor::dari('Merek', fn (ModelAset $m): string => BacaRelasi::teks(BacaRelasi::model($m, 'merek'), 'Nama')),
                KolomEkspor::dari('Kategori', fn (ModelAset $m): string => BacaRelasi::teks(BacaRelasi::model($m, 'kategoriAset'), 'Nama')),
                KolomEkspor::atribut('Produsen', 'Produsen'),
                KolomEkspor::atribut('Interval Pemeliharaan (hari)', 'IntervalPemeliharaanHari'),
                KolomEkspor::atribut('Interval Kalibrasi (hari)', 'IntervalKalibrasiHari'),
                KolomEkspor::atribut('Umur Manfaat (bulan)', 'UmurManfaatBulan'),
            ],
            'daftar-model-aset',
            EksporDaftar::formatDari($request),
        );
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ModelAset::class);

        $daftar = $this->daftar($request);

        return Inertia::render('ModelAset/Index', [
            'wajib' => ['modelAset' => AturanWajib::untuk(SimpanModelAsetRequest::class)],
            'modelAset' => ModelAsetResource::collection($daftar->halaman()),
            'filter' => $daftar->filterBerlaku(),
            'kategoriAset' => KategoriAsetResource::collection(KategoriAset::query()->orderBy('Nama')->get()),
            'merek' => MerekResource::collection(Merek::query()->orderBy('Nama')->get()),
        ]);
    }

    public function store(SimpanModelAsetRequest $request, BuatModelAset $aksi): RedirectResponse
    {
        $this->authorize('create', ModelAset::class);

        $aksi->jalankan($request->validated());

        return back()->with('sukses', 'Model aset berhasil dibuat.');
    }

    public function update(SimpanModelAsetRequest $request, ModelAset $modelAset, UbahModelAset $aksi): RedirectResponse
    {
        $this->authorize('update', $modelAset);

        $aksi->jalankan($modelAset, $request->validated());

        return back()->with('sukses', 'Model aset berhasil diperbarui.');
    }

    public function destroy(ModelAset $modelAset, HapusModelAset $aksi): RedirectResponse
    {
        $this->authorize('delete', $modelAset);

        $aksi->jalankan($modelAset);

        return back()->with('sukses', 'Model aset berhasil dihapus.');
    }
}
