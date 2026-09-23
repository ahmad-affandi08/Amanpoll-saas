<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Http\Controllers;

use App\Domain\Persediaan\Application\Actions\BuatKategoriSukuCadang;
use App\Domain\Persediaan\Application\Actions\HapusKategoriSukuCadang;
use App\Domain\Persediaan\Application\Actions\UbahKategoriSukuCadang;
use App\Domain\Persediaan\Http\Requests\SimpanKategoriSukuCadangRequest;
use App\Domain\Persediaan\Http\Resources\KategoriSukuCadangResource;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\KategoriSukuCadang;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Ekspor\EksporDaftar;
use App\Shared\Infrastructure\Ekspor\KolomEkspor;
use App\Shared\Infrastructure\Persistence\DaftarTersaring;
use App\Shared\Infrastructure\Validasi\AturanWajib;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class KategoriSukuCadangController extends Controller
{
    /**
     * Penyaring daftar, dipakai bersama halaman dan ekspornya.
     *
     * @return DaftarTersaring<KategoriSukuCadang>
     */
    private function daftar(Request $request): DaftarTersaring
    {
        return DaftarTersaring::untuk($request, KategoriSukuCadang::query()->with('induk'))
            ->cari(['Kode', 'Nama'])
            ->urut(['Nama', 'Kode'], bawaan: 'Nama');
    }

    public function ekspor(Request $request, EksporDaftar $ekspor): StreamedResponse
    {
        $this->authorize('viewAny', KategoriSukuCadang::class);

        return $ekspor->unduh(
            $this->daftar($request)->kueriTersaring(),
            [
                KolomEkspor::atribut('Kode', 'Kode'),
                KolomEkspor::atribut('Nama', 'Nama'),
            ],
            'daftar-kategori-suku-cadang',
            EksporDaftar::formatDari($request),
        );
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', KategoriSukuCadang::class);

        $daftar = $this->daftar($request);

        return Inertia::render('KategoriSukuCadang/Index', [
            'wajib' => ['kategoriSukuCadang' => AturanWajib::untuk(SimpanKategoriSukuCadangRequest::class)],
            'kategoriSukuCadang' => KategoriSukuCadangResource::collection($daftar->halaman()),
            'filter' => $daftar->filterBerlaku(),
            // Pemilih induk harus memuat seluruh kategori, bukan hanya yang tampil di halaman ini.
            'pilihanInduk' => KategoriSukuCadang::query()->orderBy('Nama')->get(['Id', 'Nama']),
        ]);
    }

    public function store(SimpanKategoriSukuCadangRequest $request, BuatKategoriSukuCadang $aksi): RedirectResponse
    {
        $this->authorize('create', KategoriSukuCadang::class);

        $aksi->jalankan($request->validated());

        return back()->with('sukses', 'Kategori suku cadang berhasil dibuat.');
    }

    public function update(SimpanKategoriSukuCadangRequest $request, KategoriSukuCadang $kategoriSukuCadang, UbahKategoriSukuCadang $aksi): RedirectResponse
    {
        $this->authorize('update', $kategoriSukuCadang);

        $aksi->jalankan($kategoriSukuCadang, $request->validated());

        return back()->with('sukses', 'Kategori suku cadang berhasil diperbarui.');
    }

    public function destroy(KategoriSukuCadang $kategoriSukuCadang, HapusKategoriSukuCadang $aksi): RedirectResponse
    {
        $this->authorize('delete', $kategoriSukuCadang);

        $aksi->jalankan($kategoriSukuCadang);

        return back()->with('sukses', 'Kategori suku cadang berhasil dihapus.');
    }
}
