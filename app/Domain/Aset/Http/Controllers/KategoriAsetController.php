<?php

declare(strict_types=1);

namespace App\Domain\Aset\Http\Controllers;

use App\Domain\Aset\Application\Actions\BuatKategoriAset;
use App\Domain\Aset\Application\Actions\HapusKategoriAset;
use App\Domain\Aset\Application\Actions\UbahKategoriAset;
use App\Domain\Aset\Http\Requests\SimpanKategoriAsetRequest;
use App\Domain\Aset\Http\Resources\KategoriAsetResource;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
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

final class KategoriAsetController extends Controller
{
    /**
     * Penyaring daftar, dipakai bersama halaman dan ekspornya.
     *
     * @return DaftarTersaring<KategoriAset>
     */
    private function daftar(Request $request): DaftarTersaring
    {
        return DaftarTersaring::untuk($request, KategoriAset::query()->with('induk'))
            ->cari(['Kode', 'Nama'])
            ->urut(['Nama', 'Kode'], bawaan: 'Nama');
    }

    public function ekspor(Request $request, EksporDaftar $ekspor): StreamedResponse
    {
        $this->authorize('viewAny', KategoriAset::class);

        return $ekspor->unduh(
            $this->daftar($request)->kueriTersaring(),
            [
                KolomEkspor::atribut('Kode', 'Kode'),
                KolomEkspor::atribut('Nama', 'Nama'),
                KolomEkspor::atribut('Umur Manfaat (bulan)', 'UmurManfaatBulan'),
                KolomEkspor::atribut('Metode Penyusutan', 'MetodePenyusutanBawaan'),
                KolomEkspor::atribut('Persentase Nilai Residu', 'PersentaseNilaiResidu'),
                KolomEkspor::dari('Perlu Kalibrasi', fn (KategoriAset $k): string => $k->MemerlukanKalibrasi ? 'Ya' : 'Tidak'),
                KolomEkspor::dari('Perlu Pemeliharaan', fn (KategoriAset $k): string => $k->MemerlukanPemeliharaan ? 'Ya' : 'Tidak'),
            ],
            'daftar-kategori-aset',
            EksporDaftar::formatDari($request),
        );
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', KategoriAset::class);

        $daftar = $this->daftar($request);

        return Inertia::render('KategoriAset/Index', [
            'wajib' => ['kategoriAset' => AturanWajib::untuk(SimpanKategoriAsetRequest::class)],
            'kategoriAset' => KategoriAsetResource::collection($daftar->halaman()),
            'filter' => $daftar->filterBerlaku(),
            // Pemilih induk harus memuat seluruh kategori, bukan hanya yang tampil di halaman ini.
            'pilihanInduk' => KategoriAset::query()->orderBy('Nama')->get(['Id', 'Nama']),
        ]);
    }

    public function store(SimpanKategoriAsetRequest $request, BuatKategoriAset $aksi): RedirectResponse
    {
        $this->authorize('create', KategoriAset::class);

        $aksi->jalankan($request->validated());

        return back()->with('sukses', 'Kategori aset berhasil dibuat.');
    }

    public function update(SimpanKategoriAsetRequest $request, KategoriAset $kategoriAset, UbahKategoriAset $aksi): RedirectResponse
    {
        $this->authorize('update', $kategoriAset);

        $aksi->jalankan($kategoriAset, $request->validated());

        return back()->with('sukses', 'Kategori aset berhasil diperbarui.');
    }

    public function destroy(KategoriAset $kategoriAset, HapusKategoriAset $aksi): RedirectResponse
    {
        $this->authorize('delete', $kategoriAset);

        $aksi->jalankan($kategoriAset);

        return back()->with('sukses', 'Kategori aset berhasil dihapus.');
    }
}
