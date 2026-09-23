<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Controllers;

use App\Domain\Platform\Application\Actions\BuatLokasi;
use App\Domain\Platform\Application\Actions\HapusLokasi;
use App\Domain\Platform\Application\Actions\UbahLokasi;
use App\Domain\Platform\Http\Requests\SimpanKategoriLokasiRequest;
use App\Domain\Platform\Http\Requests\SimpanLokasiRequest;
use App\Domain\Platform\Http\Resources\KategoriLokasiResource;
use App\Domain\Platform\Http\Resources\LokasiResource;
use App\Domain\Platform\Http\Resources\UnitOrganisasiResource;
use App\Domain\Platform\Infrastructure\Persistence\Models\KategoriLokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
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

final class LokasiController extends Controller
{
    /**
     * Penyaring daftar, dipakai bersama halaman dan ekspornya.
     *
     * @return DaftarTersaring<Lokasi>
     */
    private function daftar(Request $request): DaftarTersaring
    {
        return DaftarTersaring::untuk($request, Lokasi::query()->with(['kategoriLokasi', 'unitOrganisasi']))
            ->cari(['Nama', 'Kode'])
            ->urut(['Nama', 'Status'], bawaan: 'Nama')
            ->faset(['Status', 'KategoriLokasiId', 'UnitOrganisasiId']);
    }

    public function ekspor(Request $request, EksporDaftar $ekspor): StreamedResponse
    {
        $this->authorize('viewAny', Lokasi::class);

        return $ekspor->unduh(
            $this->daftar($request)->kueriTersaring(),
            [
                KolomEkspor::atribut('Kode', 'Kode'),
                KolomEkspor::atribut('Nama', 'Nama'),
                KolomEkspor::dari('Kategori', fn (Lokasi $l): string => BacaRelasi::teks(BacaRelasi::model($l, 'kategoriLokasi'), 'Nama')),
                KolomEkspor::dari('Unit', fn (Lokasi $l): string => BacaRelasi::teks(BacaRelasi::model($l, 'unitOrganisasi'), 'Nama')),
                KolomEkspor::atribut('Kode Ruang ASPAK', 'KodeRuangAspak'),
                KolomEkspor::atribut('Lantai', 'Lantai'),
                KolomEkspor::atribut('Alamat', 'Alamat'),
                KolomEkspor::atribut('Zona Waktu', 'ZonaWaktu'),
                KolomEkspor::atribut('Status', 'Status'),
            ],
            'daftar-lokasi',
            EksporDaftar::formatDari($request),
        );
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Lokasi::class);

        $daftar = $this->daftar($request);

        return Inertia::render('Lokasi/Index', [
            'wajib' => ['lokasi' => AturanWajib::untuk(SimpanLokasiRequest::class), 'kategori' => AturanWajib::untuk(SimpanKategoriLokasiRequest::class)],
            'lokasi' => LokasiResource::collection($daftar->halaman()),
            'filter' => $daftar->filterBerlaku(),
            'unitOrganisasi' => UnitOrganisasiResource::collection(UnitOrganisasi::query()->where('Status', 'Aktif')->orderBy('Nama')->get()),
            'kategoriLokasi' => KategoriLokasiResource::collection(KategoriLokasi::query()->orderBy('Nama')->get()),
        ]);
    }

    public function store(SimpanLokasiRequest $request, BuatLokasi $aksi): RedirectResponse
    {
        $this->authorize('create', Lokasi::class);

        $aksi->jalankan($request->validated());

        return back()->with('sukses', 'Lokasi berhasil dibuat.');
    }

    public function update(SimpanLokasiRequest $request, Lokasi $lokasi, UbahLokasi $aksi): RedirectResponse
    {
        $this->authorize('update', $lokasi);

        $aksi->jalankan($lokasi, $request->validated());

        return back()->with('sukses', 'Lokasi berhasil diperbarui.');
    }

    public function destroy(Lokasi $lokasi, HapusLokasi $aksi): RedirectResponse
    {
        $this->authorize('delete', $lokasi);

        $aksi->jalankan($lokasi);

        return back()->with('sukses', 'Lokasi berhasil dihapus.');
    }
}
