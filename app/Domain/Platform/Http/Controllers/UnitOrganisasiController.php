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
use App\Shared\Infrastructure\Persistence\DaftarTersaring;
use App\Shared\Infrastructure\Validasi\AturanWajib;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class UnitOrganisasiController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', UnitOrganisasi::class);

        $daftar = DaftarTersaring::untuk($request, UnitOrganisasi::query())
            ->cari(['Kode', 'Nama', 'Email'])
            ->urut(['Urutan', 'Nama', 'Kode', 'Jenis', 'Status'], bawaan: 'Urutan')
            ->faset(['Jenis', 'Status']);

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
