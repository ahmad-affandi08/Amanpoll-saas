<?php

declare(strict_types=1);

namespace App\Domain\Kalibrasi\Http\Controllers;

use App\Domain\Kalibrasi\Application\Actions\KelolaJenisKalibrasi;
use App\Domain\Kalibrasi\Http\Requests\SimpanJenisKalibrasiRequest;
use App\Domain\Kalibrasi\Http\Requests\SimpanTitikUkurKalibrasiRequest;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\JenisKalibrasi;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\TitikUkurKalibrasi;
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

final class JenisKalibrasiController extends Controller
{
    public function __construct(
        private readonly KelolaJenisKalibrasi $kelolaJenis,
    ) {}

    /**
     * Penyaring daftar jenis kalibrasi, dipakai bersama halaman dan ekspornya.
     *
     * @return DaftarTersaring<JenisKalibrasi>
     */
    private function daftar(Request $request): DaftarTersaring
    {
        return DaftarTersaring::untuk(
            $request,
            JenisKalibrasi::query()
                ->with(['titikUkur'])
                ->withCount(['titikUkur', 'rencanaKalibrasi', 'pelaksanaanKalibrasi']),
        )
            ->cari(['Kode', 'Nama', 'Deskripsi'])
            ->urut(['Kode', 'Nama', 'Aktif'], bawaan: 'Nama')
            ->faset(['Aktif']);
    }

    /** Katalog metode kalibrasi beserta seberapa sering dipakai. */
    public function ekspor(Request $request, EksporDaftar $ekspor): StreamedResponse
    {
        $this->authorize('viewAny', JenisKalibrasi::class);

        return $ekspor->unduh(
            $this->daftar($request)->kueriTersaring(),
            [
                KolomEkspor::atribut('Kode', 'Kode'),
                KolomEkspor::atribut('Nama', 'Nama'),
                KolomEkspor::atribut('Deskripsi', 'Deskripsi'),
                KolomEkspor::atribut('Jumlah Titik Ukur Standar', 'titik_ukur_count'),
                KolomEkspor::atribut('Jumlah Rencana', 'rencana_kalibrasi_count'),
                KolomEkspor::atribut('Jumlah Pelaksanaan', 'pelaksanaan_kalibrasi_count'),
                KolomEkspor::dari('Aktif', fn (JenisKalibrasi $jenis): string => $jenis->Aktif ? 'Ya' : 'Tidak'),
            ],
            'daftar-jenis-kalibrasi',
            EksporDaftar::formatDari($request),
        );
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', JenisKalibrasi::class);

        $daftar = $this->daftar($request);

        return Inertia::render('Kalibrasi/Jenis/Index', [
            'wajib' => ['jenis' => AturanWajib::untuk(SimpanJenisKalibrasiRequest::class), 'titikUkur' => AturanWajib::untuk(SimpanTitikUkurKalibrasiRequest::class)],
            // Barisnya disusun di sini karena serialisasi bawaan Eloquent mengubah nama
            // relasi jadi 'titik_ukur', sementara dialog titik ukur membaca 'titikUkur'.
            'jenisKalibrasi' => $daftar->halamanTerpeta(fn (JenisKalibrasi $satu): array => [
                'Id' => $satu->Id,
                'OrganisasiId' => $satu->OrganisasiId,
                'Kode' => $satu->Kode,
                'Nama' => $satu->Nama,
                'Deskripsi' => $satu->Deskripsi,
                'Aktif' => $satu->Aktif,
                'titikUkur' => $satu->titikUkur,
            ]),
            'filter' => $daftar->filterBerlaku(),
        ]);
    }

    public function store(SimpanJenisKalibrasiRequest $request): RedirectResponse
    {
        $this->authorize('create', JenisKalibrasi::class);

        $this->kelolaJenis->buat(
            $request->validated(),
            $request->user('web')->Id
        );

        return back()->with('sukses', 'Jenis kalibrasi berhasil ditambahkan.');
    }

    public function update(SimpanJenisKalibrasiRequest $request, JenisKalibrasi $jenisKalibrasi): RedirectResponse
    {
        $this->authorize('update', $jenisKalibrasi);

        $this->kelolaJenis->perbarui(
            $jenisKalibrasi,
            $request->validated(),
            $request->user('web')->Id
        );

        return back()->with('sukses', 'Jenis kalibrasi berhasil diperbarui.');
    }

    public function destroy(JenisKalibrasi $jenisKalibrasi, Request $request): RedirectResponse
    {
        $this->authorize('delete', $jenisKalibrasi);

        $this->kelolaJenis->hapus(
            $jenisKalibrasi,
            $request->user('web')->Id
        );

        return back()->with('sukses', 'Jenis kalibrasi berhasil dihapus.');
    }

    public function tambahTitikUkur(SimpanTitikUkurKalibrasiRequest $request, JenisKalibrasi $jenisKalibrasi): RedirectResponse
    {
        $this->authorize('update', $jenisKalibrasi);

        $this->kelolaJenis->tambahTitikUkur(
            $jenisKalibrasi,
            $request->validated(),
            $request->user('web')->Id
        );

        return back()->with('sukses', 'Titik ukur standar berhasil ditambahkan.');
    }

    public function updateTitikUkur(SimpanTitikUkurKalibrasiRequest $request, TitikUkurKalibrasi $titikUkurKalibrasi): RedirectResponse
    {
        $this->authorize('update', $titikUkurKalibrasi);

        $this->kelolaJenis->perbaruiTitikUkur(
            $titikUkurKalibrasi,
            $request->validated(),
            $request->user('web')->Id
        );

        return back()->with('sukses', 'Titik ukur standar berhasil diperbarui.');
    }

    public function hapusTitikUkur(TitikUkurKalibrasi $titikUkurKalibrasi, Request $request): RedirectResponse
    {
        $this->authorize('delete', $titikUkurKalibrasi);

        $this->kelolaJenis->hapusTitikUkur(
            $titikUkurKalibrasi,
            $request->user('web')->Id
        );

        return back()->with('sukses', 'Titik ukur standar berhasil dihapus.');
    }
}
