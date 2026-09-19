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
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class MerekController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Merek::class);

        $merek = Merek::query()->orderBy('Nama')->get();

        return Inertia::render('Merek/Index', [
            'merek' => MerekResource::collection($merek),
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
