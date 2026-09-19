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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class UnitOrganisasiController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', UnitOrganisasi::class);

        $status = $request->string('status')->toString();

        $unit = UnitOrganisasi::query()
            ->when($status !== '', fn ($query) => $query->where('Status', $status))
            ->orderBy('Urutan')
            ->orderBy('Nama')
            ->get();

        return Inertia::render('UnitOrganisasi/Index', [
            'unitOrganisasi' => UnitOrganisasiResource::collection($unit),
            'status' => $status,
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
