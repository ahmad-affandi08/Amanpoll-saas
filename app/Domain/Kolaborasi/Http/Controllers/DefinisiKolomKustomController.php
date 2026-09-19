<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Http\Controllers;

use App\Core\Entitas\RegistriEntitas;
use App\Domain\Kolaborasi\Application\Actions\BuatDefinisiKolomKustom;
use App\Domain\Kolaborasi\Application\Actions\HapusDefinisiKolomKustom;
use App\Domain\Kolaborasi\Application\Actions\UbahDefinisiKolomKustom;
use App\Domain\Kolaborasi\Http\Requests\SimpanDefinisiKolomKustomRequest;
use App\Domain\Kolaborasi\Http\Resources\DefinisiKolomKustomResource;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\DefinisiKolomKustom;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Inertia\Response;

final class DefinisiKolomKustomController extends Controller
{
    public function __construct(private readonly RegistriEntitas $registriEntitas) {}

    public function halaman(): Response
    {
        return Inertia::render('KolomKustom/Index', [
            'jenisEntitasTersedia' => $this->registriEntitas->jenisDikenal(),
        ]);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $data = $request->validate(['jenisEntitas' => ['required', 'string']]);
        $this->registriEntitas->pastikanBolehKelola($request->user(), $data['jenisEntitas']);

        $definisi = DefinisiKolomKustom::query()
            ->where('JenisEntitas', $data['jenisEntitas'])
            ->orderBy('Urutan')
            ->orderBy('Label')
            ->get();

        return DefinisiKolomKustomResource::collection($definisi);
    }

    public function store(SimpanDefinisiKolomKustomRequest $request, BuatDefinisiKolomKustom $aksi): RedirectResponse
    {
        $data = $request->validated();
        $this->registriEntitas->pastikanBolehKelola($request->user(), $data['JenisEntitas']);

        $aksi->jalankan($data);

        return back()->with('sukses', 'Kolom kustom berhasil dibuat.');
    }

    public function update(SimpanDefinisiKolomKustomRequest $request, DefinisiKolomKustom $definisiKolomKustom, UbahDefinisiKolomKustom $aksi): RedirectResponse
    {
        $this->registriEntitas->pastikanBolehKelola($request->user(), $definisiKolomKustom->JenisEntitas);

        $aksi->jalankan($definisiKolomKustom, $request->validated());

        return back()->with('sukses', 'Kolom kustom berhasil diperbarui.');
    }

    public function destroy(DefinisiKolomKustom $definisiKolomKustom, HapusDefinisiKolomKustom $aksi, Request $request): RedirectResponse
    {
        $this->registriEntitas->pastikanBolehKelola($request->user(), $definisiKolomKustom->JenisEntitas);

        $aksi->jalankan($definisiKolomKustom);

        return back()->with('sukses', 'Kolom kustom berhasil dihapus.');
    }
}
