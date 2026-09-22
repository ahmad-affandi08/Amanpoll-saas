<?php

declare(strict_types=1);

namespace App\Domain\Aset\Http\Controllers;

use App\Domain\Aset\Application\Actions\BuatMerek;
use App\Domain\Aset\Application\Actions\HapusMerek;
use App\Domain\Aset\Application\Actions\UbahMerek;
use App\Domain\Aset\Http\Requests\SimpanMerekRequest;
use App\Domain\Aset\Http\Resources\MerekResource;
use App\Domain\Aset\Infrastructure\Persistence\Models\Merek;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Persistence\DaftarTersaring;
use App\Shared\Infrastructure\Validasi\AturanWajib;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class MerekController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Merek::class);

        $daftar = DaftarTersaring::untuk($request, Merek::query())
            ->cari(['Nama', 'NegaraAsal'])
            ->urut(['Nama', 'NegaraAsal'], bawaan: 'Nama');

        return Inertia::render('Merek/Index', [
            'wajib' => ['merek' => AturanWajib::untuk(SimpanMerekRequest::class)],
            'merek' => MerekResource::collection($daftar->halaman()),
            'filter' => $daftar->filterBerlaku(),
        ]);
    }

    public function store(SimpanMerekRequest $request, BuatMerek $aksi): RedirectResponse
    {
        $this->authorize('create', Merek::class);

        $aksi->jalankan($request->validated());

        return back()->with('sukses', 'Merek berhasil dibuat.');
    }

    public function update(SimpanMerekRequest $request, Merek $merek, UbahMerek $aksi): RedirectResponse
    {
        $this->authorize('update', $merek);

        $aksi->jalankan($merek, $request->validated());

        return back()->with('sukses', 'Merek berhasil diperbarui.');
    }

    public function destroy(Merek $merek, HapusMerek $aksi): RedirectResponse
    {
        $this->authorize('delete', $merek);

        $aksi->jalankan($merek);

        return back()->with('sukses', 'Merek berhasil dihapus.');
    }
}
