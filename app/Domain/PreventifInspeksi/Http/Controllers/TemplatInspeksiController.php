<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Http\Controllers;

use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\PreventifInspeksi\Application\Actions\KelolaInspeksi;
use App\Domain\PreventifInspeksi\Http\Requests\SimpanTemplatInspeksiRequest;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\TemplatDaftarPeriksa;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\TemplatInspeksi;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class TemplatInspeksiController extends Controller
{
    public function __construct(
        private readonly KelolaInspeksi $kelolaInspeksi,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', TemplatInspeksi::class);

        $daftarTemplat = TemplatInspeksi::query()
            ->with(['kategoriAset', 'templatDaftarPeriksa'])
            ->withCount('inspeksi')
            ->orderBy('Nama')
            ->get();

        $kategoriAset = KategoriAset::query()->orderBy('Nama')->get(['Id', 'Nama']);
        $templatDaftarPeriksa = TemplatDaftarPeriksa::query()->where('Aktif', true)->orderBy('Nama')->get(['Id', 'Nama', 'Kode']);

        return Inertia::render('Inspeksi/Templat/Index', [
            'templat' => $daftarTemplat,
            'kategoriAset' => $kategoriAset,
            'templatDaftarPeriksa' => $templatDaftarPeriksa,
        ]);
    }

    public function store(SimpanTemplatInspeksiRequest $request): RedirectResponse
    {
        $this->authorize('create', TemplatInspeksi::class);

        $this->kelolaInspeksi->buatTemplat(
            $request->validated(),
            $request->user('web')->Id
        );

        return back()->with('sukses', 'Templat inspeksi berhasil dibuat.');
    }

    public function update(SimpanTemplatInspeksiRequest $request, TemplatInspeksi $templatInspeksi): RedirectResponse
    {
        $this->authorize('update', $templatInspeksi);

        $this->kelolaInspeksi->perbaruiTemplat(
            $templatInspeksi,
            $request->validated(),
            $request->user('web')->Id
        );

        return back()->with('sukses', 'Templat inspeksi berhasil diperbarui.');
    }
}
