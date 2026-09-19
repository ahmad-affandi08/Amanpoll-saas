<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Controllers;

use App\Domain\Platform\Application\Actions\BuatPeran;
use App\Domain\Platform\Application\Actions\HapusPeran;
use App\Domain\Platform\Application\Actions\SinkronkanIzinPeran;
use App\Domain\Platform\Application\Actions\UbahPeran;
use App\Domain\Platform\Application\DTO\PeranData;
use App\Domain\Platform\Http\Requests\SimpanPeranRequest;
use App\Domain\Platform\Http\Resources\PeranResource;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PeranController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Peran::class);

        $peran = Peran::query()
            ->withCount(['penggunaPeran', 'peranIzin'])
            ->with('peranIzin')
            ->orderBy('Nama')
            ->get();

        return Inertia::render('PeranIzin/Index', [
            'peran' => PeranResource::collection($peran),
        ]);
    }

    public function store(SimpanPeranRequest $request, BuatPeran $aksi): RedirectResponse
    {
        $this->authorize('create', Peran::class);

        $aksi->jalankan(PeranData::dariArray($request->validated()));

        return back()->with('sukses', 'Peran berhasil dibuat.');
    }

    public function update(SimpanPeranRequest $request, Peran $peran, UbahPeran $aksi): RedirectResponse
    {
        $this->authorize('update', $peran);

        $aksi->jalankan($peran, PeranData::dariArray($request->validated()));

        return back()->with('sukses', 'Peran berhasil diperbarui.');
    }

    public function destroy(Peran $peran, HapusPeran $aksi): RedirectResponse
    {
        $this->authorize('delete', $peran);

        $aksi->jalankan($peran);

        return back()->with('sukses', 'Peran berhasil dihapus.');
    }

    public function sinkronkanIzin(Request $request, Peran $peran, SinkronkanIzinPeran $aksi): RedirectResponse
    {
        $this->authorize('update', $peran);

        $data = $request->validate([
            'IzinId' => ['array'],
            'IzinId.*' => ['string'],
        ]);

        $aksi->jalankan($peran, $data['IzinId'] ?? []);

        return back()->with('sukses', 'Izin peran berhasil diperbarui.');
    }
}
