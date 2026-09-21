<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Http\Controllers;

use App\Domain\Langganan\Application\Actions\KelolaPaketLangganan;
use App\Domain\Langganan\Domain\KatalogFitur;
use App\Domain\Langganan\Domain\ValueObjects\DefinisiFitur;
use App\Domain\Langganan\Http\Requests\SimpanPaketPlatformRequest;
use App\Domain\Langganan\Infrastructure\Persistence\Models\PaketFitur;
use App\Domain\Langganan\Infrastructure\Persistence\Models\PaketLangganan;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * CRUD katalog paket oleh admin platform (22.02/22.03).
 */
final class PaketPlatformController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Platform/Paket/Index', [
            'paket' => PaketLangganan::query()
                ->with('fitur.fiturPaket')
                ->withCount(['langganan as JumlahLangganan'])
                ->orderBy('Nama')
                ->get()
                ->map(fn (PaketLangganan $paket): array => $this->ringkas($paket))
                ->all(),
            'katalogFitur' => array_values(array_map(
                fn (DefinisiFitur $definisi): array => $definisi->keArray(),
                KatalogFitur::semua(),
            )),
        ]);
    }

    public function store(SimpanPaketPlatformRequest $request, KelolaPaketLangganan $aksi): RedirectResponse
    {
        $aksi->simpan($request->validated());

        return back()->with('sukses', 'Paket langganan dibuat.');
    }

    public function update(
        SimpanPaketPlatformRequest $request,
        PaketLangganan $paketLangganan,
        KelolaPaketLangganan $aksi,
    ): RedirectResponse {
        $aksi->simpan($request->validated(), $paketLangganan);

        return back()->with('sukses', 'Paket langganan diperbarui.');
    }

    public function destroy(PaketLangganan $paketLangganan, KelolaPaketLangganan $aksi): RedirectResponse
    {
        $aksi->hapus($paketLangganan);

        return back()->with('sukses', 'Paket langganan dihapus.');
    }

    /** @return array<string, mixed> */
    private function ringkas(PaketLangganan $paket): array
    {
        return [
            'Id' => $paket->Id,
            'Kode' => $paket->Kode,
            'Nama' => $paket->Nama,
            'Deskripsi' => $paket->Deskripsi,
            'HargaBulanan' => (float) $paket->HargaBulanan,
            'HargaTahunan' => (float) $paket->HargaTahunan,
            'MataUang' => $paket->MataUang,
            'Aktif' => (bool) $paket->Aktif,
            'JumlahLangganan' => (int) ($paket->JumlahLangganan ?? 0),
            'Fitur' => $paket->fitur
                ->map(fn (PaketFitur $baris): array => [
                    'Kode' => (string) ($baris->fiturPaket->Kode ?? ''),
                    'Diizinkan' => (bool) $baris->Diizinkan,
                    'BatasNilai' => $baris->BatasNilai === null ? null : (float) $baris->BatasNilai,
                ])
                ->filter(fn (array $baris): bool => $baris['Kode'] !== '')
                ->values()
                ->all(),
        ];
    }
}
