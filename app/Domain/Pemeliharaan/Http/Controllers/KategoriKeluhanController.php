<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Http\Controllers;

use App\Domain\Pemeliharaan\Application\Actions\HapusKategoriKeluhan;
use App\Domain\Pemeliharaan\Application\Actions\SimpanKategoriKeluhan;
use App\Domain\Pemeliharaan\Http\Requests\SimpanKategoriKeluhanRequest;
use App\Domain\Pemeliharaan\Http\Resources\KategoriKeluhanResource;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KategoriKeluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\TingkatLayanan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Persistence\DaftarTersaring;
use App\Shared\Infrastructure\Validasi\AturanWajib;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class KategoriKeluhanController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', KategoriKeluhan::class);

        $daftar = DaftarTersaring::untuk(
            $request,
            KategoriKeluhan::query()->with(['induk', 'tingkatLayanan', 'peranPenanggungJawab']),
        )
            ->cari(['Kode', 'Nama'])
            ->urut(['Nama', 'Kode', 'PrioritasBawaan'], bawaan: 'Nama')
            ->faset(['PrioritasBawaan']);

        return Inertia::render('KategoriKeluhan/Index', [
            'wajib' => ['kategoriKeluhan' => AturanWajib::untuk(SimpanKategoriKeluhanRequest::class)],
            'kategori' => KategoriKeluhanResource::collection($daftar->halaman()),
            'filter' => $daftar->filterBerlaku(),
            // Pemilih induk harus memuat seluruh kategori, bukan hanya yang tampil di halaman ini.
            'pilihanInduk' => KategoriKeluhan::query()->orderBy('Nama')->get(['Id', 'Nama']),
            'tingkatLayanan' => TingkatLayanan::query()->where('Aktif', true)->orderBy('Nama')->get(['Id', 'Nama']),
            'peran' => Peran::query()->orderBy('Nama')->get(['Id', 'Nama']),
        ]);
    }

    public function store(SimpanKategoriKeluhanRequest $request, SimpanKategoriKeluhan $aksi): RedirectResponse
    {
        $this->authorize('create', KategoriKeluhan::class);
        $aksi->jalankan($request->validated());

        return back()->with('sukses', 'Kategori keluhan berhasil dibuat.');
    }

    public function update(SimpanKategoriKeluhanRequest $request, KategoriKeluhan $kategoriKeluhan, SimpanKategoriKeluhan $aksi): RedirectResponse
    {
        $this->authorize('update', $kategoriKeluhan);
        $aksi->jalankan($request->validated(), $kategoriKeluhan);

        return back()->with('sukses', 'Kategori keluhan berhasil diperbarui.');
    }

    public function destroy(KategoriKeluhan $kategoriKeluhan, HapusKategoriKeluhan $aksi): RedirectResponse
    {
        $this->authorize('delete', $kategoriKeluhan);
        $aksi->jalankan($kategoriKeluhan);

        return back()->with('sukses', 'Kategori keluhan berhasil dihapus.');
    }
}
