<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Core\Cadangan\LayananCadangan;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Console\Command;

/**
 * Mengunduh cadangan dari disk luar ke folder cadangan lokal (FASE 45).
 *
 * Arsip berkas dipulihkan dengan `tar`, bukan lewat artisan (RUNBOOK-PEMULIHAN),
 * jadi setelah server hilang arsipnya perlu diambil dulu dengan perintah ini.
 * Unduhan diverifikasi terhadap ukuran dan checksum salinan luarnya.
 */
final class AmbilCadangan extends Command
{
    protected $signature = 'cadangan:ambil {nama* : Nama berkas cadangan, lihat cadangan:daftar --luar}';

    protected $description = 'Unduh cadangan dari disk luar ke folder cadangan lokal';

    public function __construct(private readonly LayananCadangan $layanan)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        foreach ($this->argument('nama') as $nama) {
            try {
                $jalur = $this->layanan->ambilDariLuar($nama);
            } catch (AturanBisnisDilanggar $galat) {
                $this->error($galat->getMessage());

                return self::FAILURE;
            }

            $this->info("{$nama} diunduh ke {$jalur}.");
        }

        return self::SUCCESS;
    }
}
