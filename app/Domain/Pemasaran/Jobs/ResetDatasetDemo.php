<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Jobs;

use App\Domain\Pemasaran\Application\Services\LayananSesiDemo;
use App\Domain\Pemasaran\Application\Services\PengaturUlangDatasetDemo;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\DemoPemasaran;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/** Membangun ulang dataset tiap demo yang sudah lewat interval resetnya (MARKETING.md 11, 29). */
final class ResetDatasetDemo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly ?string $kodeDemo = null)
    {
        $this->onConnection('database');
    }

    public function handle(PengaturUlangDatasetDemo $pengatur, LayananSesiDemo $sesi): void
    {
        $sesi->tutupYangKedaluwarsa();

        $demo = DemoPemasaran::query()
            ->when($this->kodeDemo !== null, fn ($kueri) => $kueri->where('Kode', $this->kodeDemo))
            ->whereNotNull('OrganisasiDemoId')
            ->get();

        foreach ($demo as $satu) {
            if (! $pengatur->sudahWaktunya($satu)) {
                continue;
            }

            // Satu demo yang setelannya salah tidak boleh menghentikan reset demo lainnya.
            try {
                $pengatur->jalankan($satu);
            } catch (AturanBisnisDilanggar $galat) {
                Log::warning('Reset dataset demo dilewati.', [
                    'Demo' => $satu->Kode,
                    'Alasan' => $galat->getMessage(),
                ]);
            }
        }
    }
}
