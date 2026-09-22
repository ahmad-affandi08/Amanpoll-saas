<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Http\Controllers;

use App\Domain\Persediaan\Application\Actions\BuatGudang;
use App\Domain\Persediaan\Application\Actions\BuatLokasiGudang;
use App\Domain\Persediaan\Application\Actions\HapusGudang;
use App\Domain\Persediaan\Application\Actions\HapusLokasiGudang;
use App\Domain\Persediaan\Application\Actions\UbahGudang;
use App\Domain\Persediaan\Application\Actions\UbahLokasiGudang;
use App\Domain\Persediaan\Http\Requests\SimpanGudangRequest;
use App\Domain\Persediaan\Http\Requests\SimpanLokasiGudangRequest;
use App\Domain\Persediaan\Http\Resources\GudangResource;
use App\Domain\Persediaan\Http\Resources\LokasiGudangResource;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\Gudang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\LokasiGudang;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Persistence\DaftarTersaring;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class GudangController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Gudang::class);

        $daftar = DaftarTersaring::untuk(
            $request,
            Gudang::query()->with(['lokasi', 'penanggungJawab', 'lokasiGudang'])->withCount('lokasiGudang'),
        )
            ->cari(['Kode', 'Nama'])
            ->urut(['Nama', 'Kode', 'Status'], bawaan: 'Nama')
            ->faset(['Status', 'LokasiId']);

        $halaman = $daftar->halaman();

        return Inertia::render('Gudang/Index', [
            'gudang' => GudangResource::collection($halaman),
            // Hanya untuk baris yang tampil; dialog lokasi tidak pernah dibuka dari baris lain.
            'lokasiGudangPerGudang' => $halaman->getCollection()->mapWithKeys(
                fn (Gudang $satu) => [$satu->Id => LokasiGudangResource::collection($satu->lokasiGudang)],
            ),
            'filter' => $daftar->filterBerlaku(),
            'lokasi' => Lokasi::query()->orderBy('Nama')->get(['Id', 'Nama']),
        ]);
    }

    public function store(SimpanGudangRequest $request, BuatGudang $aksi): RedirectResponse
    {
        $this->authorize('create', Gudang::class);

        $aksi->jalankan($request->validated());

        return back()->with('sukses', 'Gudang berhasil dibuat.');
    }

    public function update(SimpanGudangRequest $request, Gudang $gudang, UbahGudang $aksi): RedirectResponse
    {
        $this->authorize('update', $gudang);

        $aksi->jalankan($gudang, $request->validated());

        return back()->with('sukses', 'Gudang berhasil diperbarui.');
    }

    public function destroy(Gudang $gudang, HapusGudang $aksi): RedirectResponse
    {
        $this->authorize('delete', $gudang);

        $aksi->jalankan($gudang);

        return back()->with('sukses', 'Gudang berhasil dihapus.');
    }

    public function storeLokasi(SimpanLokasiGudangRequest $request, Gudang $gudang, BuatLokasiGudang $aksi): RedirectResponse
    {
        $this->authorize('update', $gudang);

        $data = $request->validated();
        $data['GudangId'] = $gudang->Id;
        $aksi->jalankan($data);

        return back()->with('sukses', 'Lokasi gudang berhasil dibuat.');
    }

    public function updateLokasi(SimpanLokasiGudangRequest $request, LokasiGudang $lokasiGudang, UbahLokasiGudang $aksi): RedirectResponse
    {
        $this->authorize('update', $lokasiGudang->gudang);

        $aksi->jalankan($lokasiGudang, $request->validated());

        return back()->with('sukses', 'Lokasi gudang berhasil diperbarui.');
    }

    public function destroyLokasi(LokasiGudang $lokasiGudang, HapusLokasiGudang $aksi): RedirectResponse
    {
        $this->authorize('update', $lokasiGudang->gudang);

        $aksi->jalankan($lokasiGudang);

        return back()->with('sukses', 'Lokasi gudang berhasil dihapus.');
    }
}
