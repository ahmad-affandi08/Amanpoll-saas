<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Kepatuhan\Application\Services\LayananKepatuhan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use Illuminate\Console\Command;

/**
 * Mengirim peringatan masa berlaku kepatuhan aset dan sertifikat, sekaligus
 * menandai yang sudah lewat sebagai kedaluwarsa (18.03, 18.04).
 */
final class PeringatanKepatuhanKedaluwarsa extends Command
{
    protected $signature = 'kepatuhan:kirim-peringatan-kedaluwarsa';

    protected $description = 'Kirim peringatan kepatuhan aset dan sertifikat yang akan atau sudah berakhir';

    public function __construct(private readonly LayananKepatuhan $layananKepatuhan)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $total = [
            'kepatuhanKedaluwarsa' => 0,
            'kepatuhanAkanBerakhir' => 0,
            'sertifikatKedaluwarsa' => 0,
            'sertifikatAkanBerakhir' => 0,
            'dilewati' => 0,
        ];

        foreach (Organisasi::query()->cursor() as $organisasi) {
            foreach ($this->layananKepatuhan->kirimPeringatan($organisasi->Id) as $kunci => $jumlah) {
                $total[$kunci] += $jumlah;
            }
        }

        $this->info(sprintf(
            'Peringatan kepatuhan selesai. Kepatuhan: %d akan berakhir, %d kedaluwarsa. Sertifikat: %d akan berakhir, %d kedaluwarsa. Dilewati: %d.',
            $total['kepatuhanAkanBerakhir'],
            $total['kepatuhanKedaluwarsa'],
            $total['sertifikatAkanBerakhir'],
            $total['sertifikatKedaluwarsa'],
            $total['dilewati'],
        ));

        return self::SUCCESS;
    }
}
