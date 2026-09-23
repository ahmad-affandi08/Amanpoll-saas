<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Http\Controllers;

use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\PreventifInspeksi\Application\Actions\KelolaInspeksi;
use App\Domain\PreventifInspeksi\Http\Requests\SimpanTemplatInspeksiRequest;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\TemplatDaftarPeriksa;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\TemplatInspeksi;
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

final class TemplatInspeksiController extends Controller
{
    public function __construct(
        private readonly KelolaInspeksi $kelolaInspeksi,
    ) {}

    /**
     * Penyaring daftar templat inspeksi, dipakai bersama halaman dan ekspornya.
     *
     * @return DaftarTersaring<TemplatInspeksi>
     */
    private function daftar(Request $request): DaftarTersaring
    {
        return DaftarTersaring::untuk(
            $request,
            TemplatInspeksi::query()->with(['kategoriAset', 'templatDaftarPeriksa'])->withCount('inspeksi'),
        )
            ->cari(['Kode', 'Nama'])
            ->urut(['Kode', 'Nama', 'IntervalHari', 'Aktif'], bawaan: 'Nama')
            ->faset(['KategoriAsetId', 'TemplatDaftarPeriksaId', 'Aktif']);
    }

    /** Siklus inspeksi per kategori aset, untuk ditinjau di luar aplikasi. */
    public function ekspor(Request $request, EksporDaftar $ekspor): StreamedResponse
    {
        $this->authorize('viewAny', TemplatInspeksi::class);

        return $ekspor->unduh(
            $this->daftar($request)->kueriTersaring(),
            [
                KolomEkspor::atribut('Kode', 'Kode'),
                KolomEkspor::atribut('Nama', 'Nama'),
                KolomEkspor::dari('Kategori Aset', fn (TemplatInspeksi $templat): string => BacaRelasi::teks(BacaRelasi::model($templat, 'kategoriAset'), 'Nama')),
                KolomEkspor::dari('Templat Daftar Periksa', fn (TemplatInspeksi $templat): string => BacaRelasi::teks(BacaRelasi::model($templat, 'templatDaftarPeriksa'), 'Nama')),
                KolomEkspor::atribut('Interval (hari)', 'IntervalHari'),
                KolomEkspor::atribut('Jumlah Inspeksi', 'inspeksi_count'),
                KolomEkspor::dari('Aktif', fn (TemplatInspeksi $templat): string => $templat->Aktif ? 'Ya' : 'Tidak'),
            ],
            'daftar-templat-inspeksi',
            EksporDaftar::formatDari($request),
        );
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', TemplatInspeksi::class);

        $daftar = $this->daftar($request);

        // Pemilih formulir memuat seluruh kategori dan checklist, bukan hanya baris halaman ini.
        $kategoriAset = KategoriAset::query()->orderBy('Nama')->get(['Id', 'Nama']);
        $templatDaftarPeriksa = TemplatDaftarPeriksa::query()->where('Aktif', true)->orderBy('Nama')->get(['Id', 'Nama', 'Kode']);

        return Inertia::render('Inspeksi/Templat/Index', [
            'wajib' => ['templat' => AturanWajib::untuk(SimpanTemplatInspeksiRequest::class)],
            // Barisnya disusun di sini supaya nama relasinya tetap camelCase; serialisasi
            // bawaan Eloquent mengubahnya jadi 'kategori_aset' dan halaman kehilangan isinya.
            'templat' => $daftar->halamanTerpeta(fn (TemplatInspeksi $satu): array => [
                'Id' => $satu->Id,
                'OrganisasiId' => $satu->OrganisasiId,
                'Kode' => $satu->Kode,
                'Nama' => $satu->Nama,
                'KategoriAsetId' => $satu->KategoriAsetId,
                'TemplatDaftarPeriksaId' => $satu->TemplatDaftarPeriksaId,
                'IntervalHari' => $satu->IntervalHari,
                'Aktif' => $satu->Aktif,
                'inspeksi_count' => (int) ($satu->inspeksi_count ?? 0),
                'kategoriAset' => $satu->kategoriAset?->only(['Id', 'Nama']),
                'templatDaftarPeriksa' => $satu->templatDaftarPeriksa?->only(['Id', 'Kode', 'Nama']),
            ]),
            'filter' => $daftar->filterBerlaku(),
            'kategoriAset' => $kategoriAset,
            'templatDaftarPeriksa' => $templatDaftarPeriksa,
        ]);
    }

    public function store(SimpanTemplatInspeksiRequest $request): RedirectResponse
    {
        $this->authorize('create', TemplatInspeksi::class);

        $this->kelolaInspeksi->buatTemplat(
            $request->validated(),
            $request->user('web')->Id
        );

        return back()->with('sukses', 'Templat inspeksi berhasil dibuat.');
    }

    public function update(SimpanTemplatInspeksiRequest $request, TemplatInspeksi $templatInspeksi): RedirectResponse
    {
        $this->authorize('update', $templatInspeksi);

        $this->kelolaInspeksi->perbaruiTemplat(
            $templatInspeksi,
            $request->validated(),
            $request->user('web')->Id
        );

        return back()->with('sukses', 'Templat inspeksi berhasil diperbarui.');
    }
}
