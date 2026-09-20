<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Http\Controllers;

use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\ModelAset;
use App\Domain\PreventifInspeksi\Application\Actions\KelolaTemplatDaftarPeriksa;
use App\Domain\PreventifInspeksi\Http\Requests\SimpanTemplatDaftarPeriksaRequest;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\TemplatDaftarPeriksa;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class TemplatDaftarPeriksaController extends Controller
{
    public function __construct(
        private readonly KelolaTemplatDaftarPeriksa $kelolaTemplat,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', TemplatDaftarPeriksa::class);

        $daftarTemplat = TemplatDaftarPeriksa::query()
            ->with(['kategoriAset', 'modelAset'])
            ->withCount('butir')
            ->orderBy('Nama')
            ->get();

        return Inertia::render('DaftarPeriksa/Templat/Index', [
            'templat' => $daftarTemplat,
            'kategoriAset' => KategoriAset::query()->orderBy('Nama')->get(['Id', 'Nama']),
            'modelAset' => ModelAset::query()->orderBy('Nama')->get(['Id', 'Nama', 'KategoriAsetId']),
        ]);
    }

    public function store(SimpanTemplatDaftarPeriksaRequest $request): RedirectResponse
    {
        $this->authorize('create', TemplatDaftarPeriksa::class);

        $templat = $this->kelolaTemplat->buat(
            $request->validated(),
            $request->user()->Id
        );

        return redirect()->route('preventifInspeksi.templat-daftar-periksa.show', $templat->Id)
            ->with('sukses', 'Templat daftar periksa berhasil dibuat.');
    }

    public function show(TemplatDaftarPeriksa $templatDaftarPeriksa): Response
    {
        $this->authorize('view', $templatDaftarPeriksa);

        $templatDaftarPeriksa->load([
            'kategoriAset',
            'modelAset',
            'butir' => fn ($q) => $q->orderBy('Urutan'),
        ]);

        return Inertia::render('DaftarPeriksa/Templat/Show', [
            'templat' => $templatDaftarPeriksa,
            'kategoriAset' => KategoriAset::query()->orderBy('Nama')->get(['Id', 'Nama']),
            'modelAset' => ModelAset::query()->orderBy('Nama')->get(['Id', 'Nama', 'KategoriAsetId']),
        ]);
    }

    public function update(SimpanTemplatDaftarPeriksaRequest $request, TemplatDaftarPeriksa $templatDaftarPeriksa): RedirectResponse
    {
        $this->authorize('update', $templatDaftarPeriksa);

        $this->kelolaTemplat->perbarui(
            $templatDaftarPeriksa,
            $request->validated(),
            $request->user()->Id
        );

        return back()->with('sukses', 'Templat daftar periksa berhasil diperbarui.');
    }

    public function buatVersiBaru(Request $request, TemplatDaftarPeriksa $templatDaftarPeriksa): RedirectResponse
    {
        $this->authorize('create', TemplatDaftarPeriksa::class);

        $versiBaru = $this->kelolaTemplat->buatVersiBaru(
            $templatDaftarPeriksa,
            $request->user()->Id
        );

        return redirect()->route('preventifInspeksi.templat-daftar-periksa.show', $versiBaru->Id)
            ->with('sukses', "Versi baru ({$versiBaru->Kode}) berhasil dibuat.");
    }

    public function destroy(Request $request, TemplatDaftarPeriksa $templatDaftarPeriksa): RedirectResponse
    {
        $this->authorize('delete', $templatDaftarPeriksa);

        $this->kelolaTemplat->hapus(
            $templatDaftarPeriksa,
            $request->user()->Id
        );

        return redirect()->route('preventifInspeksi.templat-daftar-periksa.index')
            ->with('sukses', 'Templat daftar periksa berhasil dihapus.');
    }
}
