<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Core\Cadangan\LayananCadangan;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/** Mencadangkan basis data dan berkas unggahan, lalu memangkas yang lewat retensi (FASE 25.04). */
final class JalankanCadangan extends Command
{
    protected $signature = 'cadangan:jalankan {--tanpa-berkas : Hanya mencadangkan basis data}';

    protected $description = 'Cadangkan basis data dan berkas unggahan, lalu pangkas cadangan lama';

    public function __construct(private readonly LayananCadangan $layanan)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $basisData = $this->layanan->cadangkanBasisData();
            $this->info('Basis data dicadangkan ke '.basename($basisData).'.');

            if (! $this->option('tanpa-berkas')) {
                $berkas = $this->layanan->cadangkanBerkas();
                $this->info($berkas === null
                    ? 'Tidak ada folder berkas untuk dicadangkan.'
                    : 'Berkas dicadangkan ke '.basename($berkas).'.');
            }

            $dipangkas = $this->layanan->pangkas();
            $this->info("Memangkas {$dipangkas} cadangan yang lewat retensi.");

            return self::SUCCESS;
        } catch (AturanBisnisDilanggar $galat) {
            // Cadangan yang gagal harus berisik; diamnya persis seperti cadangan yang berhasil.
            Log::error('Pencadangan gagal.', ['Pesan' => $galat->getMessage()]);
            $this->error($galat->getMessage());

            return self::FAILURE;
        }
    }
}
