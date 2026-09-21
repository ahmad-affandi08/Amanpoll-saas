<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Kontrak\Application\Services\LayananPeringatanKontrak;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use Illuminate\Console\Command;

/**
 * Mengirim peringatan kontrak yang mendekati tanggal berakhir dan menutup
 * kontrak yang sudah lewat masa berlakunya (17.04).
 */
final class PeringatanKontrakBerakhir extends Command
{
    protected $signature = 'kontrak:kirim-peringatan-berakhir';

    protected $description = 'Kirim peringatan kontrak akan berakhir dan tutup kontrak yang kedaluwarsa';

    public function __construct(private readonly LayananPeringatanKontrak $layananPeringatan)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $total = ['akanBerakhir' => 0, 'kedaluwarsa' => 0, 'ditutup' => 0, 'dilewati' => 0];

        foreach (Organisasi::query()->cursor() as $organisasi) {
            foreach ($this->layananPeringatan->kirimPeringatan($organisasi->Id) as $kunci => $jumlah) {
                $total[$kunci] += $jumlah;
            }
        }

        $this->info(sprintf(
            'Peringatan kontrak selesai. Akan berakhir: %d, kedaluwarsa: %d, ditutup otomatis: %d, dilewati (duplikat): %d.',
            $total['akanBerakhir'],
            $total['kedaluwarsa'],
            $total['ditutup'],
            $total['dilewati'],
        ));

        return self::SUCCESS;
    }
}
