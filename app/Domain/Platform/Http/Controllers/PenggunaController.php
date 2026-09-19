<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Controllers;

use App\Domain\Platform\Application\Actions\BuatPengguna;
use App\Domain\Platform\Application\Actions\UbahPengguna;
use App\Domain\Platform\Application\Actions\UbahStatusPengguna;
use App\Domain\Platform\Application\DTO\PenggunaData;
use App\Domain\Platform\Http\Requests\SimpanPenggunaRequest;
use App\Domain\Platform\Http\Resources\PenggunaResource;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class PenggunaController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Pengguna::class);

        $pengguna = Pengguna::query()
            ->with(['penggunaPeran.peran'])
            ->orderBy('Nama')
            ->get();

        return Inertia::render('Pengguna/Index', [
            'pengguna' => PenggunaResource::collection($pengguna),
            'peranTersedia' => Peran::query()->orderBy('Nama')->get(['Id', 'Nama']),
        ]);
    }

    public function store(SimpanPenggunaRequest $request, BuatPengguna $aksi): RedirectResponse
    {
        $this->authorize('create', Pengguna::class);

        $aksi->jalankan(PenggunaData::dariArray($request->validated()));

        return back()->with('sukses', 'Pengguna berhasil dibuat.');
    }

    public function update(SimpanPenggunaRequest $request, Pengguna $pengguna, UbahPengguna $aksi): RedirectResponse
    {
        $this->authorize('update', $pengguna);

        $aksi->jalankan($pengguna, PenggunaData::dariArray($request->validated()));

        return back()->with('sukses', 'Pengguna berhasil diperbarui.');
    }

    public function ubahStatus(Request $request, Pengguna $pengguna, UbahStatusPengguna $aksi): RedirectResponse
    {
        $this->authorize('ubahStatus', $pengguna);

        $data = $request->validate([
            'Status' => ['required', Rule::in(['Aktif', 'Nonaktif'])],
        ]);

        $aksi->jalankan($request->user(), $pengguna, $data['Status']);

        return back()->with('sukses', 'Status pengguna berhasil diperbarui.');
    }
}
