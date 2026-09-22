<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Controllers;

use App\Core\Audit\LayananAudit;
use App\Domain\Pemasaran\Application\Services\PengaturUlangDatasetDemo;
use App\Domain\Pemasaran\Application\Services\RegistriDatasetDemo;
use App\Domain\Pemasaran\Domain\Enums\FiturDibatasiDemo;
use App\Domain\Pemasaran\Domain\Enums\JenisEventDemo;
use App\Domain\Pemasaran\Domain\Enums\ModulDemo;
use App\Domain\Pemasaran\Domain\Enums\StatusSesiDemo;
use App\Domain\Pemasaran\Http\Requests\SimpanDemoPemasaranRequest;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\DemoPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\SesiDemo;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/** Konsol demo produk (MARKETING.md 11). */
final class DemoPemasaranController extends Controller
{
    public function __construct(
        private readonly RegistriDatasetDemo $registri,
        private readonly PengaturUlangDatasetDemo $pengatur,
        private readonly LayananAudit $audit,
    ) {}

    public function index(): Response
    {
        $demo = DemoPemasaran::query()->with('organisasiDemo:Id,Kode,Nama,Demo')->orderBy('Nama')->get();

        // Jumlah sesi per demo dibaca sekali sebagai peta, bukan satu kueri per baris.
        $sesi = SesiDemo::query()
            ->selectRaw('DemoPemasaranId, Status, COUNT(*) as Jumlah')
            ->groupBy('DemoPemasaranId', 'Status')
            ->get();

        return Inertia::render('Pemasaran/Demo', [
            'demo' => $demo->map(fn (DemoPemasaran $satu): array => [
                'Id' => $satu->Id,
                'Kode' => $satu->Kode,
                'Nama' => $satu->Nama,
                'Aktif' => $satu->Aktif,
                'Dataset' => $satu->Dataset,
                'ResetIntervalMenit' => $satu->ResetIntervalMenit,
                'ModulTampil' => $satu->ModulTampil ?? [],
                'FiturDibatasi' => $satu->FiturDibatasi ?? [],
                'CtaLabel' => $satu->CtaLabel,
                'CtaUrl' => $satu->CtaUrl,
                'MaksDurasiMenit' => $satu->MaksDurasiMenit,
                'MaksSesiSerentak' => $satu->MaksSesiSerentak,
                'TerakhirResetPada' => $satu->TerakhirResetPada?->toIso8601String(),
                'TenantDemo' => $satu->organisasiDemo === null
                    ? null
                    : ['Kode' => $satu->organisasiDemo->Kode, 'Demo' => $satu->organisasiDemo->Demo],
                'SesiBerjalan' => (int) $sesi
                    ->where('DemoPemasaranId', $satu->Id)
                    ->where('Status', StatusSesiDemo::Berjalan->value)
                    ->sum('Jumlah'),
                'SesiSelesai' => (int) $sesi
                    ->where('DemoPemasaranId', $satu->Id)
                    ->where('Status', StatusSesiDemo::Selesai->value)
                    ->sum('Jumlah'),
            ])->all(),
            'peristiwa' => $this->peristiwa(),
            'pilihan' => [
                'Dataset' => $this->registri->nama(),
                'Modul' => array_column(ModulDemo::cases(), 'value'),
                'Fitur' => array_column(FiturDibatasiDemo::cases(), 'value'),
            ],
        ]);
    }

    public function store(SimpanDemoPemasaranRequest $request): RedirectResponse
    {
        $demo = DemoPemasaran::create($request->validated());

        $this->audit->catat('Demo.Dibuat', 'DemoPemasaran', $demo->Id, dataSesudah: [
            'Kode' => $demo->Kode,
            'Dataset' => $demo->Dataset,
        ]);

        return back()->with('sukses', 'Demo berhasil dibuat.');
    }

    public function update(SimpanDemoPemasaranRequest $request, DemoPemasaran $demo): RedirectResponse
    {
        $sebelum = ['Aktif' => $demo->Aktif, 'Dataset' => $demo->Dataset];
        $demo->update($request->validated());

        $this->audit->catat(
            'Demo.Diubah',
            'DemoPemasaran',
            $demo->Id,
            dataSebelum: $sebelum,
            dataSesudah: ['Aktif' => $demo->Aktif, 'Dataset' => $demo->Dataset],
        );

        return back()->with('sukses', 'Demo berhasil diperbarui.');
    }

    /** Reset manual memakai penjaga yang sama dengan reset terjadwal, tanpa jalan pintas. */
    public function reset(DemoPemasaran $demo): RedirectResponse
    {
        $this->pengatur->jalankan($demo);

        $this->audit->catat('Demo.Direset', 'DemoPemasaran', $demo->Id, dataSesudah: [
            'Dataset' => $demo->Dataset,
            'TenantDemoId' => $demo->OrganisasiDemoId,
        ]);

        return back()->with('sukses', 'Dataset demo dibangun ulang.');
    }

    /** @return array<string, int> */
    private function peristiwa(): array
    {
        $jumlah = DB::table('EventDemo')
            ->selectRaw('Jenis, COUNT(*) as Jumlah')
            ->groupBy('Jenis')
            ->pluck('Jumlah', 'Jenis');

        $hasil = [];

        foreach (JenisEventDemo::cases() as $satu) {
            $hasil[$satu->value] = (int) ($jumlah[$satu->value] ?? 0);
        }

        return $hasil;
    }
}
