<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Http\Controllers;

use App\Core\Entitas\RegistriEntitas;
use App\Domain\Kolaborasi\Application\Actions\LepaskanTagDariEntitas;
use App\Domain\Kolaborasi\Application\Actions\TambahkanTagKeEntitas;
use App\Domain\Kolaborasi\Http\Requests\SimpanEntitasTagRequest;
use App\Domain\Kolaborasi\Http\Resources\EntitasTagResource;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\EntitasTag;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class EntitasTagController extends Controller
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

        $entitasTag = EntitasTag::query()
            ->with('tag')
            ->where('JenisEntitas', $data['jenisEntitas'])
            ->where('EntitasId', $data['entitasId'])
            ->latest('DibuatPada')
            ->get();

        return EntitasTagResource::collection($entitasTag);
    }

    public function store(SimpanEntitasTagRequest $request, TambahkanTagKeEntitas $aksi): RedirectResponse
    {
        $data = $request->validated();
        $this->registriEntitas->pastikanBolehKelola($request->user('web'), $data['JenisEntitas']);

        $aksi->jalankan($data['TagId'], $data['JenisEntitas'], $data['EntitasId']);

        return back()->with('sukses', 'Tag berhasil ditambahkan.');
    }

    public function destroy(EntitasTag $entitasTag, LepaskanTagDariEntitas $aksi, Request $request): RedirectResponse
    {
        $this->registriEntitas->pastikanBolehKelola($request->user('web'), $entitasTag->JenisEntitas);

        $aksi->jalankan($entitasTag);

        return back()->with('sukses', 'Tag berhasil dilepas.');
    }
}
