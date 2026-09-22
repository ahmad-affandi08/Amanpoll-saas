<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Kalibrasi\Application\Services\LayananPeringatanKalibrasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use Illuminate\Console\Command;

/** Memeriksa kalibrasi yang segera jatuh tempo atau terlambat di setiap organisasi. */
final class PeringatanJatuhTempoKalibrasi extends Command
{
    protected $signature = 'kalibrasi:kirim-peringatan-jatuh-tempo';

    protected $description = 'Kirim notifikasi peringatan untuk rencana kalibrasi yang segera jatuh tempo atau terlambat';

    public function __construct(private readonly LayananPeringatanKalibrasi $layananPeringatan)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $totalSegera = 0;
        $totalTerlambat = 0;
        $totalDilewati = 0;

        foreach (Organisasi::query()->cursor() as $organisasi) {
            $hasil = $this->layananPeringatan->kirimPeringatan($organisasi->Id);

            $totalSegera += $hasil['segeraJatuhTempo'];
            $totalTerlambat += $hasil['terlambat'];
            $totalDilewati += $hasil['dilewati'];
        }

        $this->info("Peringatan kalibrasi selesai dikirim. Segera jatuh tempo: {$totalSegera}, Terlambat: {$totalTerlambat}, Dilewati (duplikat): {$totalDilewati}.");

        return self::SUCCESS;
    }
}
