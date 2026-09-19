<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Http\Controllers;

use App\Domain\Aset\Http\Resources\AsetResource;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Platform\Http\Resources\PenggunaResource;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\SiklusAset\Application\Actions\BuatSerahTerimaAset;
use App\Domain\SiklusAset\Application\Actions\TambahDetailSerahTerimaAset;
use App\Domain\SiklusAset\Application\Actions\TerimaSerahTerimaAset;
use App\Domain\SiklusAset\Http\Requests\SimpanDetailSerahTerimaAsetRequest;
use App\Domain\SiklusAset\Http\Requests\SimpanSerahTerimaAsetRequest;
use App\Domain\SiklusAset\Http\Requests\TerimaSerahTerimaAsetRequest;
use App\Domain\SiklusAset\Http\Resources\SerahTerimaAsetResource;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\SerahTerimaAset;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class SerahTerimaAsetController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', SerahTerimaAset::class);

        $filter = $request->validate(['status' => ['nullable', 'string']]);

        $serahTerima = SerahTerimaAset::query()
            ->with(['pihakMenyerahkan', 'pihakMenerima'])
            ->when($filter['status'] ?? null, fn ($q, $v) => $q->where('Status', $v))
            ->latest('DibuatPada')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('SerahTerimaAset/Index', [
            'serahTerima' => SerahTerimaAsetResource::collection($serahTerima),
            'filter' => $filter,
        ]);
    }

    public function show(SerahTerimaAset $serahTerimaAset): Response
    {
        $this->authorize('view', $serahTerimaAset);

        $serahTerimaAset->load(['pihakMenyerahkan', 'pihakMenerima', 'detailSerahTerimaAset.aset']);

        return Inertia::render('SerahTerimaAset/Show', [
            'serahTerima' => new SerahTerimaAsetResource($serahTerimaAset),
            'aset' => AsetResource::collection(Aset::query()->orderBy('Nama')->get()),
            'pengguna' => PenggunaResource::collection(Pengguna::query()->where('Status', 'Aktif')->orderBy('Nama')->get()),
        ]);
    }

    public function store(SimpanSerahTerimaAsetRequest $request, BuatSerahTerimaAset $aksi): RedirectResponse
    {
        $this->authorize('create', SerahTerimaAset::class);

        $serahTerima = $aksi->jalankan($request->validated());

        return redirect("/serah-terima-aset/{$serahTerima->Id}")->with('sukses', 'Dokumen serah terima berhasil dibuat.');
    }

    public function storeDetail(SimpanDetailSerahTerimaAsetRequest $request, SerahTerimaAset $serahTerimaAset, TambahDetailSerahTerimaAset $aksi): RedirectResponse
    {
        $this->authorize('update', $serahTerimaAset);

        $data = $request->validated();
        $aksi->jalankan($serahTerimaAset, $data['AsetId'], $data['KondisiSaatDiserahkan'] ?? null, $data['Catatan'] ?? null);

        return back()->with('sukses', 'Aset berhasil ditambahkan ke dokumen serah terima.');
    }

    public function terima(TerimaSerahTerimaAsetRequest $request, SerahTerimaAset $serahTerimaAset, TerimaSerahTerimaAset $aksi): RedirectResponse
    {
        $this->authorize('update', $serahTerimaAset);

        $aksi->jalankan($serahTerimaAset, $request->validated()['Detail']);

        return back()->with('sukses', 'Serah terima aset berhasil dikonfirmasi diterima.');
    }
}
