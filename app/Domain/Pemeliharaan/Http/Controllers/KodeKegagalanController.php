<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Http\Controllers;

use App\Core\Izin\PemeriksaIzin;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Pemeliharaan\Application\Actions\SimpanKodeKegagalan;
use App\Domain\Pemeliharaan\Http\Requests\SimpanKodeKegagalanRequest;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KodeKegagalan;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class KodeKegagalanController extends Controller
{
    public function __construct(private readonly PemeriksaIzin $izin) {}

    public function index(Request $request): Response
    {
        $this->pastikanBerizin($request);

        return Inertia::render('KodeKegagalan/Index', [
            'kodeKegagalan' => KodeKegagalan::query()->with('kategoriAset')->orderBy('Jenis')->orderBy('Kode')->get(),
            'kategoriAset' => KategoriAset::query()->orderBy('Nama')->get(['Id', 'Nama']),
        ]);
    }

    public function store(SimpanKodeKegagalanRequest $request, SimpanKodeKegagalan $aksi): RedirectResponse
    {
        $this->pastikanBerizin($request);
        $aksi->jalankan($request->validated());

        return back()->with('sukses', 'Kode kegagalan berhasil dibuat.');
    }

    public function update(SimpanKodeKegagalanRequest $request, KodeKegagalan $kodeKegagalan, SimpanKodeKegagalan $aksi): RedirectResponse
    {
        $this->pastikanBerizin($request);
        $aksi->jalankan($request->validated(), $kodeKegagalan);

        return back()->with('sukses', 'Kode kegagalan berhasil diperbarui.');
    }

    private function pastikanBerizin(Request $request): void
    {
        abort_unless($this->izin->boleh($request->user()->Id, 'PerintahKerja.Kelola'), 403);
    }
}
