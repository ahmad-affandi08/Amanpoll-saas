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
use App\Shared\Infrastructure\Validasi\AturanWajib;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class KodeKegagalanController extends Controller
{
    public function __construct(private readonly PemeriksaIzin $izin) {}

    /**
     * Daftar kode kegagalan, dipakai bersama halaman dan ekspornya.
     *
     * @return Builder<KodeKegagalan>
     */
    private function kueriTersaring(): Builder
    {
        return KodeKegagalan::query()
            ->with('kategoriAset')
            ->orderBy('Jenis')
            ->orderBy('Kode')
            ->orderBy('Id');
    }

    /** Taksonomi Problem-Cause-Remedy, untuk disepakati bersama di luar aplikasi. */
    public function ekspor(Request $request, EksporDaftar $ekspor): StreamedResponse
    {
        $this->pastikanBerizin($request);

        return $ekspor->unduh(
            $this->kueriTersaring(),
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

        return Inertia::render('KodeKegagalan/Index', [
            'wajib' => ['kodeKegagalan' => AturanWajib::untuk(SimpanKodeKegagalanRequest::class)],
            'kodeKegagalan' => $this->kueriTersaring()->get(),
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
