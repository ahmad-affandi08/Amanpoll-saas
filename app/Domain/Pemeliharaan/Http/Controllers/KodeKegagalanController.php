<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Http\Controllers;

use App\Core\Izin\PemeriksaIzin;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Pemeliharaan\Application\Actions\SimpanKodeKegagalan;
use App\Domain\Pemeliharaan\Http\Requests\SimpanKodeKegagalanRequest;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KodeKegagalan;
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

final class KodeKegagalanController extends Controller
{
    public function __construct(private readonly PemeriksaIzin $izin) {}

    /**
     * Penyaring daftar kode kegagalan, dipakai bersama halaman dan ekspornya.
     *
     * @return DaftarTersaring<KodeKegagalan>
     */
    private function daftar(Request $request): DaftarTersaring
    {
        return DaftarTersaring::untuk($request, KodeKegagalan::query()->with('kategoriAset'))
            ->cari(['Kode', 'Nama', 'Keterangan'])
            ->urut(['Kode', 'Nama', 'Jenis', 'Aktif'], bawaan: 'Kode')
            ->faset(['Jenis', 'KategoriAsetId', 'Aktif']);
    }

    /** Taksonomi Problem-Cause-Remedy, untuk disepakati bersama di luar aplikasi. */
    public function ekspor(Request $request, EksporDaftar $ekspor): StreamedResponse
    {
        $this->pastikanBerizin($request);

        return $ekspor->unduh(
            $this->daftar($request)->kueriTersaring(),
            [
                KolomEkspor::atribut('Kode', 'Kode'),
                KolomEkspor::atribut('Nama', 'Nama'),
                KolomEkspor::atribut('Jenis', 'Jenis'),
                KolomEkspor::dari('Kategori Aset', fn (KodeKegagalan $kode): string => BacaRelasi::teks(BacaRelasi::model($kode, 'kategoriAset'), 'Nama')),
                KolomEkspor::atribut('Keterangan', 'Keterangan'),
                KolomEkspor::dari('Aktif', fn (KodeKegagalan $kode): string => $kode->Aktif ? 'Ya' : 'Tidak'),
            ],
            'daftar-kode-kegagalan',
            EksporDaftar::formatDari($request),
        );
    }

    public function index(Request $request): Response
    {
        $this->pastikanBerizin($request);

        $daftar = $this->daftar($request);

        return Inertia::render('KodeKegagalan/Index', [
            'wajib' => ['kodeKegagalan' => AturanWajib::untuk(SimpanKodeKegagalanRequest::class)],
            'kodeKegagalan' => $daftar->halamanTerpeta(fn (KodeKegagalan $satu): array => [
                'Id' => $satu->Id,
                'Kode' => $satu->Kode,
                'Nama' => $satu->Nama,
                'Jenis' => $satu->Jenis,
                'KategoriAsetId' => $satu->KategoriAsetId,
                'NamaKategoriAset' => $satu->kategoriAset?->Nama,
                'Keterangan' => $satu->Keterangan,
                'Aktif' => $satu->Aktif,
            ]),
            'filter' => $daftar->filterBerlaku(),
            // Pemilih formulir memuat seluruh kategori, bukan hanya baris halaman ini.
            'kategoriAset' => KategoriAset::query()->orderBy('Nama')->get(['Id', 'Nama']),
        ]);
    }

    public function store(SimpanKodeKegagalanRequest $request, SimpanKodeKegagalan $aksi): RedirectResponse
    {
        $this->pastikanBerizin($request);
        $aksi->jalankan($request->validated());

        return back()->with('sukses', 'Kode kegagalan berhasil dibuat.');
    }

    public function update(SimpanKodeKegagalanRequest $request, KodeKegagalan $kodeKegagalan, SimpanKodeKegagalan $aksi): RedirectResponse
    {
        $this->pastikanBerizin($request);
        $aksi->jalankan($request->validated(), $kodeKegagalan);

        return back()->with('sukses', 'Kode kegagalan berhasil diperbarui.');
    }

    private function pastikanBerizin(Request $request): void
    {
        abort_unless($this->izin->boleh($request->user('web')->Id, 'PerintahKerja.Kelola'), 403);
    }
}
