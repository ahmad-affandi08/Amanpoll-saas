<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Controllers;

use App\Domain\Platform\Application\Actions\BuatUnitOrganisasi;
use App\Domain\Platform\Application\Actions\HapusUnitOrganisasi;
use App\Domain\Platform\Application\Actions\UbahUnitOrganisasi;
use App\Domain\Platform\Http\Requests\SimpanUnitOrganisasiRequest;
use App\Domain\Platform\Http\Resources\UnitOrganisasiResource;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
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

final class UnitOrganisasiController extends Controller
{
    /**
     * Penyaring daftar, dipakai bersama halaman dan ekspornya.
     *
     * @return DaftarTersaring<UnitOrganisasi>
     */
    private function daftar(Request $request): DaftarTersaring
    {
        return DaftarTersaring::untuk($request, UnitOrganisasi::query())
            ->cari(['Kode', 'Nama', 'Email'])
            ->urut(['Urutan', 'Nama', 'Kode', 'Jenis', 'Status'], bawaan: 'Urutan')
            ->faset(['Jenis', 'Status', 'MengelolaAset']);
    }

    public function ekspor(Request $request, EksporDaftar $ekspor): StreamedResponse
    {
        $this->authorize('viewAny', UnitOrganisasi::class);

        return $ekspor->unduh(
            $this->daftar($request)->kueriTersaring(),
            [
                KolomEkspor::atribut('Kode', 'Kode'),
                KolomEkspor::atribut('Nama', 'Nama'),
                KolomEkspor::atribut('Jenis', 'Jenis'),
                KolomEkspor::atribut('Email', 'Email'),
                KolomEkspor::atribut('Telepon', 'Telepon'),
                KolomEkspor::atribut('Urutan', 'Urutan'),
                KolomEkspor::atribut('Status', 'Status'),
                KolomEkspor::dari('Mengelola Aset', fn (UnitOrganisasi $unit): string => $unit->MengelolaAset ? 'Ya' : 'Tidak'),
            ],
            'daftar-unit-organisasi',
            EksporDaftar::formatDari($request),
        );
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', UnitOrganisasi::class);

        $daftar = $this->daftar($request);

        return Inertia::render('UnitOrganisasi/Index', [
            'wajib' => ['unit' => AturanWajib::untuk(SimpanUnitOrganisasiRequest::class)],
            'unitOrganisasi' => UnitOrganisasiResource::collection($daftar->halaman()),
            'filter' => $daftar->filterBerlaku(),
            // Pemilih induk harus memuat seluruh unit, bukan hanya yang tampil di halaman ini.
            'pilihanInduk' => UnitOrganisasi::query()->orderBy('Nama')->get(['Id', 'Nama']),
        ]);
    }

    public function store(SimpanUnitOrganisasiRequest $request, BuatUnitOrganisasi $aksi): RedirectResponse
    {
        $this->authorize('create', UnitOrganisasi::class);

        $aksi->jalankan($request->validated());

        return back()->with('sukses', 'Unit organisasi berhasil dibuat.');
    }

    public function update(SimpanUnitOrganisasiRequest $request, UnitOrganisasi $unit, UbahUnitOrganisasi $aksi): RedirectResponse
    {
        $this->authorize('update', $unit);

        $aksi->jalankan($unit, $request->validated());

        return back()->with('sukses', 'Unit organisasi berhasil diperbarui.');
    }

    public function destroy(UnitOrganisasi $unit, HapusUnitOrganisasi $aksi): RedirectResponse
    {
        $this->authorize('delete', $unit);

        $aksi->jalankan($unit);

        return back()->with('sukses', 'Unit organisasi berhasil dihapus.');
    }
}
