<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Http\Controllers;

use App\Domain\Notifikasi\Application\Actions\BuatTemplatNotifikasi;
use App\Domain\Notifikasi\Application\Actions\HapusTemplatNotifikasi;
use App\Domain\Notifikasi\Application\Actions\UbahTemplatNotifikasi;
use App\Domain\Notifikasi\Http\Requests\SimpanTemplatNotifikasiRequest;
use App\Domain\Notifikasi\Http\Resources\TemplatNotifikasiResource;
use App\Domain\Notifikasi\Infrastructure\Persistence\Models\TemplatNotifikasi;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class TemplatNotifikasiController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', TemplatNotifikasi::class);

        $templat = TemplatNotifikasi::query()->orderBy('Kode')->orderBy('Kanal')->get();

        return Inertia::render('TemplatNotifikasi/Index', [
            'templatNotifikasi' => TemplatNotifikasiResource::collection($templat),
        ]);
    }

    public function store(SimpanTemplatNotifikasiRequest $request, BuatTemplatNotifikasi $aksi): RedirectResponse
    {
        $this->authorize('create', TemplatNotifikasi::class);

        $aksi->jalankan($request->validated());

        return back()->with('sukses', 'Templat notifikasi berhasil dibuat.');
    }

    public function update(SimpanTemplatNotifikasiRequest $request, TemplatNotifikasi $templatNotifikasi, UbahTemplatNotifikasi $aksi): RedirectResponse
    {
        $this->authorize('update', $templatNotifikasi);

        $aksi->jalankan($templatNotifikasi, $request->validated());

        return back()->with('sukses', 'Templat notifikasi berhasil diperbarui.');
    }

    public function destroy(TemplatNotifikasi $templatNotifikasi, HapusTemplatNotifikasi $aksi): RedirectResponse
    {
        $this->authorize('delete', $templatNotifikasi);

        $aksi->jalankan($templatNotifikasi);

        return back()->with('sukses', 'Templat notifikasi berhasil dihapus.');
    }
}
