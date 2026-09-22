<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Controllers;

use App\Domain\Platform\Application\Actions\BuatLokasi;
use App\Domain\Platform\Application\Actions\HapusLokasi;
use App\Domain\Platform\Application\Actions\UbahLokasi;
use App\Domain\Platform\Http\Requests\SimpanLokasiRequest;
use App\Domain\Platform\Http\Resources\KategoriLokasiResource;
use App\Domain\Platform\Http\Resources\LokasiResource;
use App\Domain\Platform\Http\Resources\UnitOrganisasiResource;
use App\Domain\Platform\Infrastructure\Persistence\Models\KategoriLokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Persistence\BatasDaftar;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class LokasiController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Lokasi::class);

        $lokasi = Lokasi::query()
            ->with(['kategoriLokasi', 'unitOrganisasi'])
            ->orderBy('Nama')
            ->limit(BatasDaftar::MAKS)
            ->get();

        return Inertia::render('Lokasi/Index', [
            'lokasi' => LokasiResource::collection($lokasi),
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
