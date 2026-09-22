<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Http\Controllers;

use App\Core\Entitas\RegistriEntitas;
use App\Domain\Kolaborasi\Application\Actions\LampirkanBerkas;
use App\Domain\Kolaborasi\Application\Actions\LepaskanLampiran;
use App\Domain\Kolaborasi\Http\Requests\SimpanLampiranEntitasRequest;
use App\Domain\Kolaborasi\Http\Resources\LampiranEntitasResource;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\LampiranEntitas;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Persistence\BatasDaftar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class LampiranEntitasController extends Controller
{
    public function __construct(private readonly RegistriEntitas $registriEntitas) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $data = $request->validate([
            'jenisEntitas' => ['required', 'string'],
            'entitasId' => ['required', 'string'],
        ]);

        $this->registriEntitas->cariEntitas($data['jenisEntitas'], $data['entitasId']);
        $this->registriEntitas->pastikanBolehKelola($request->user('web'), $data['jenisEntitas']);

        $lampiran = LampiranEntitas::query()
            ->with('berkas')
            ->where('JenisEntitas', $data['jenisEntitas'])
            ->where('EntitasId', $data['entitasId'])
            ->latest('DibuatPada')
            ->limit(BatasDaftar::MAKS)
            ->get();

        return LampiranEntitasResource::collection($lampiran);
    }

    public function store(SimpanLampiranEntitasRequest $request, LampirkanBerkas $aksi): RedirectResponse
    {
        $data = $request->validated();
        $this->registriEntitas->pastikanBolehKelola($request->user('web'), $data['JenisEntitas']);

        $aksi->jalankan(
            $data['JenisEntitas'],
            $data['EntitasId'],
            $data['BerkasId'],
            $data['Kategori'] ?? null,
            $data['Keterangan'] ?? null,
            $request->user('web')->Id,
        );

        return back()->with('sukses', 'Lampiran berhasil ditambahkan.');
    }

    public function destroy(LampiranEntitas $lampiranEntitas, LepaskanLampiran $aksi, Request $request): RedirectResponse
    {
        $this->registriEntitas->pastikanBolehKelola($request->user('web'), $lampiranEntitas->JenisEntitas);

        $aksi->jalankan($lampiranEntitas);

        return back()->with('sukses', 'Lampiran berhasil dihapus.');
    }
}
