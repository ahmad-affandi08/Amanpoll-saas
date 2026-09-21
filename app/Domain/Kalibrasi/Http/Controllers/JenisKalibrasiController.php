<?php

declare(strict_types=1);

namespace App\Domain\Kalibrasi\Http\Controllers;

use App\Domain\Kalibrasi\Application\Actions\KelolaJenisKalibrasi;
use App\Domain\Kalibrasi\Http\Requests\SimpanJenisKalibrasiRequest;
use App\Domain\Kalibrasi\Http\Requests\SimpanTitikUkurKalibrasiRequest;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\JenisKalibrasi;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\TitikUkurKalibrasi;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class JenisKalibrasiController extends Controller
{
    public function __construct(
        private readonly KelolaJenisKalibrasi $kelolaJenis,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', JenisKalibrasi::class);

        $daftarJenis = JenisKalibrasi::query()
            ->with(['titikUkur'])
            ->withCount(['rencanaKalibrasi', 'pelaksanaanKalibrasi'])
            ->orderBy('Nama')
            ->get();

        return Inertia::render('Kalibrasi/Jenis/Index', [
            'jenisKalibrasi' => $daftarJenis,
        ]);
    }

    public function store(SimpanJenisKalibrasiRequest $request): RedirectResponse
    {
        $this->authorize('create', JenisKalibrasi::class);

        $this->kelolaJenis->buat(
            $request->validated(),
            $request->user('web')->Id
        );

        return back()->with('sukses', 'Jenis kalibrasi berhasil ditambahkan.');
    }

    public function update(SimpanJenisKalibrasiRequest $request, JenisKalibrasi $jenisKalibrasi): RedirectResponse
    {
        $this->authorize('update', $jenisKalibrasi);

        $this->kelolaJenis->perbarui(
            $jenisKalibrasi,
            $request->validated(),
            $request->user('web')->Id
        );

        return back()->with('sukses', 'Jenis kalibrasi berhasil diperbarui.');
    }

    public function destroy(JenisKalibrasi $jenisKalibrasi, Request $request): RedirectResponse
    {
        $this->authorize('delete', $jenisKalibrasi);

        $this->kelolaJenis->hapus(
            $jenisKalibrasi,
            $request->user('web')->Id
        );

        return back()->with('sukses', 'Jenis kalibrasi berhasil dihapus.');
    }

    public function tambahTitikUkur(SimpanTitikUkurKalibrasiRequest $request, JenisKalibrasi $jenisKalibrasi): RedirectResponse
    {
        $this->authorize('update', $jenisKalibrasi);

        $this->kelolaJenis->tambahTitikUkur(
            $jenisKalibrasi,
            $request->validated(),
            $request->user('web')->Id
        );

        return back()->with('sukses', 'Titik ukur standar berhasil ditambahkan.');
    }

    public function updateTitikUkur(SimpanTitikUkurKalibrasiRequest $request, TitikUkurKalibrasi $titikUkurKalibrasi): RedirectResponse
    {
        $this->authorize('update', $titikUkurKalibrasi);

        $this->kelolaJenis->perbaruiTitikUkur(
            $titikUkurKalibrasi,
            $request->validated(),
            $request->user('web')->Id
        );

        return back()->with('sukses', 'Titik ukur standar berhasil diperbarui.');
    }

    public function hapusTitikUkur(TitikUkurKalibrasi $titikUkurKalibrasi, Request $request): RedirectResponse
    {
        $this->authorize('delete', $titikUkurKalibrasi);

        $this->kelolaJenis->hapusTitikUkur(
            $titikUkurKalibrasi,
            $request->user('web')->Id
        );

        return back()->with('sukses', 'Titik ukur standar berhasil dihapus.');
    }
}
