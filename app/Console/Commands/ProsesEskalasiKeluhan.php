<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Pemeliharaan\Jobs\ProsesEskalasiKeluhan as JobEskalasiKeluhan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('keluhan:proses-eskalasi-sla')]
#[Description('Antrekan pemeriksaan SLA keluhan untuk setiap organisasi')]
final class ProsesEskalasiKeluhan extends Command
{
    public function handle(): int
    {
        $jumlah = 0;
        foreach (Organisasi::query()->where('Status', 'Aktif')->cursor() as $organisasi) {
            JobEskalasiKeluhan::dispatch($organisasi->Id);
            $jumlah++;
        }

        $this->info("Mengantrekan pemeriksaan SLA untuk {$jumlah} organisasi.");

        return self::SUCCESS;
    }
}
