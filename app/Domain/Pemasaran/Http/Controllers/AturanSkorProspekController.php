<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Controllers;

use App\Domain\Pemasaran\Application\Services\LayananAturanSkorProspek;
use App\Domain\Pemasaran\Domain\KatalogPeristiwaSkor;
use App\Domain\Pemasaran\Http\Requests\SimpanAturanSkorProspekRequest;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\AturanSkorProspek;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Persistence\DaftarTersaring;
use App\Shared\Infrastructure\Validasi\AturanWajib;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Aturan bobot skor prospek di konsol platform (MARKETING.md 5.4). */
final class AturanSkorProspekController extends Controller
{
    public function __construct(private readonly LayananAturanSkorProspek $layanan) {}

    /**
     * Dihitung di basis data, bukan dari baris yang kebetulan tampil.
     *
     * Spanduk peringatannya berbicara tentang seluruh aturan; menghitungnya dari
     * satu halaman akan membuat angkanya menyusut saat pengguna berpindah halaman.
     *
     * @return array{jumlah: int, peristiwa: list<string>}
     */
    private function jumlahBelumBerlaku(): array
    {
        $peristiwa = AturanSkorProspek::query()
            ->where('Aktif', true)
            ->get()
            ->filter(fn (AturanSkorProspek $satu): bool => ! $satu->berlaku())
            ->map(fn (AturanSkorProspek $satu): string => $satu->Peristiwa)
            ->all();

        $daftar = array_values($peristiwa);

        return ['jumlah' => count($daftar), 'peristiwa' => $daftar];
    }

    public function index(Request $request): Response
    {
        $daftar = DaftarTersaring::untuk($request, AturanSkorProspek::query()->withCount('rincianSkor'))
            ->cari(['Peristiwa', 'Keterangan'])
            ->urut(['Peristiwa', 'Bobot'], bawaan: 'Bobot', arahBawaan: 'desc');

        return Inertia::render('Pemasaran/AturanSkor/Index', [
            'wajib' => ['aturan' => AturanWajib::untuk(SimpanAturanSkorProspekRequest::class)],
            'aturan' => $daftar->halamanTerpeta(fn (AturanSkorProspek $satu): array => [
                'Id' => $satu->Id,
                'Peristiwa' => $satu->Peristiwa,
                'Bobot' => $satu->Bobot,
                'Aktif' => $satu->Aktif,
                'Keterangan' => $satu->Keterangan,
                'Asal' => $satu->asal(),
                'Berlaku' => $satu->berlaku(),
                'JumlahDipakai' => (int) ($satu->rincian_skor_count ?? 0),
            ]),
            'filter' => $daftar->filterBerlaku(),
            'jumlahBelumBerlaku' => $this->jumlahBelumBerlaku(),
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
