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
use App\Shared\Infrastructure\Persistence\DaftarTersaring;
use App\Shared\Infrastructure\Validasi\AturanWajib;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class TemplatNotifikasiController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', TemplatNotifikasi::class);

        $daftar = DaftarTersaring::untuk($request, TemplatNotifikasi::query())
            ->cari(['Kode', 'JudulTemplat'])
            ->urut(['Kode', 'Kanal'], bawaan: 'Kode')
            ->faset(['Kanal']);

        return Inertia::render('TemplatNotifikasi/Index', [
            'wajib' => ['templat' => AturanWajib::untuk(SimpanTemplatNotifikasiRequest::class)],
            'templatNotifikasi' => TemplatNotifikasiResource::collection($daftar->halaman()),
            'filter' => $daftar->filterBerlaku(),
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
