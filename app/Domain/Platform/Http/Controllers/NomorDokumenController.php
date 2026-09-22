<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Controllers;

use App\Domain\Platform\Application\Actions\BuatNomorDokumen;
use App\Domain\Platform\Application\Actions\HapusNomorDokumen;
use App\Domain\Platform\Application\Actions\UbahNomorDokumen;
use App\Domain\Platform\Http\Requests\SimpanNomorDokumenRequest;
use App\Domain\Platform\Http\Resources\NomorDokumenResource;
use App\Domain\Platform\Infrastructure\Persistence\Models\NomorDokumen;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Persistence\DaftarTersaring;
use App\Shared\Infrastructure\Validasi\AturanWajib;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class NomorDokumenController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', NomorDokumen::class);

        $daftar = DaftarTersaring::untuk($request, NomorDokumen::query())
            ->cari(['JenisDokumen', 'Awalan'])
            ->urut(['JenisDokumen', 'Awalan', 'ResetPeriode'], bawaan: 'JenisDokumen')
            ->faset(['ResetPeriode']);

        return Inertia::render('NomorDokumen/Index', [
            'wajib' => ['nomorDokumen' => AturanWajib::untuk(SimpanNomorDokumenRequest::class)],
            'nomorDokumen' => NomorDokumenResource::collection($daftar->halaman()),
            'filter' => $daftar->filterBerlaku(),
        ]);
    }

    public function store(SimpanNomorDokumenRequest $request, BuatNomorDokumen $aksi): RedirectResponse
    {
        $this->authorize('create', NomorDokumen::class);

        $aksi->jalankan($request->validated());

        return back()->with('sukses', 'Pola nomor dokumen berhasil dibuat.');
    }

    public function update(SimpanNomorDokumenRequest $request, NomorDokumen $nomorDokumen, UbahNomorDokumen $aksi): RedirectResponse
    {
        $this->authorize('update', $nomorDokumen);

        $aksi->jalankan($nomorDokumen, $request->validated());

        return back()->with('sukses', 'Pola nomor dokumen berhasil diperbarui.');
    }

    public function destroy(NomorDokumen $nomorDokumen, HapusNomorDokumen $aksi): RedirectResponse
    {
        $this->authorize('delete', $nomorDokumen);

        $aksi->jalankan($nomorDokumen);

        return back()->with('sukses', 'Pola nomor dokumen berhasil dihapus.');
    }
}
