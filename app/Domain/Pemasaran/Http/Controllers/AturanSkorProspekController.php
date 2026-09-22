<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Controllers;

use App\Domain\Pemasaran\Application\Services\LayananAturanSkorProspek;
use App\Domain\Pemasaran\Domain\KatalogPeristiwaSkor;
use App\Domain\Pemasaran\Http\Requests\SimpanAturanSkorProspekRequest;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\AturanSkorProspek;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/** Aturan bobot skor prospek di konsol platform (MARKETING.md 5.4). */
final class AturanSkorProspekController extends Controller
{
    public function __construct(private readonly LayananAturanSkorProspek $layanan) {}

    public function index(): Response
    {
        $aturan = AturanSkorProspek::query()
            ->withCount('rincianSkor')
            ->orderByDesc('Bobot')
            ->get();

        return Inertia::render('Pemasaran/AturanSkor/Index', [
            'aturan' => $aturan->map(fn (AturanSkorProspek $satu): array => [
                'Id' => $satu->Id,
                'Peristiwa' => $satu->Peristiwa,
                'Bobot' => $satu->Bobot,
                'Aktif' => $satu->Aktif,
                'Keterangan' => $satu->Keterangan,
                'Asal' => $satu->asal(),
                'Berlaku' => $satu->berlaku(),
                'JumlahDipakai' => (int) ($satu->rincian_skor_count ?? 0),
            ])->all(),
            'pilihan' => [
                'Peristiwa' => KatalogPeristiwaSkor::semua(),
                'AsalTertunda' => KatalogPeristiwaSkor::ASAL_TERTUNDA,
            ],
        ]);
    }

    public function store(SimpanAturanSkorProspekRequest $request): RedirectResponse
    {
        $this->layanan->simpan(null, $request->validated());

        return back()->with('sukses', 'Aturan skor berhasil dibuat.');
    }

    public function update(
        SimpanAturanSkorProspekRequest $request,
        AturanSkorProspek $aturan,
    ): RedirectResponse {
        $this->layanan->simpan($aturan, $request->validated());

        return back()->with('sukses', 'Aturan skor berhasil diperbarui.');
    }

    public function destroy(AturanSkorProspek $aturan): RedirectResponse
    {
        $this->layanan->hapus($aturan);

        return back()->with('sukses', 'Aturan skor berhasil dihapus.');
    }
}
