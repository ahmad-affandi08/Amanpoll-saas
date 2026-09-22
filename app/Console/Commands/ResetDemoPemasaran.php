<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Pemasaran\Jobs\ResetDatasetDemo;
use Illuminate\Console\Command;

/** Membangun ulang dataset demo yang sudah lewat interval resetnya (MARKETING.md 11). */
final class ResetDemoPemasaran extends Command
{
    protected $signature = 'pemasaran:reset-demo {--kode= : Reset satu demo saja}';

    protected $description = 'Bangun ulang dataset demo dan tutup sesi yang kedaluwarsa';

    public function handle(): int
    {
        $kode = $this->option('kode');

        ResetDatasetDemo::dispatchSync(is_string($kode) && $kode !== '' ? $kode : null);

        $this->info('Reset dataset demo selesai.');

        return self::SUCCESS;
    }
}
