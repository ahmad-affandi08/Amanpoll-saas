<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Pemasaran\Application\Services\PemeriksaAlertPemasaran;
use App\Domain\Pemasaran\Jobs\HitungMetrikKampanye;
use Illuminate\Console\Command;

/** Menghitung metrik kampanye harian lalu memeriksa alert growth (MARKETING.md 5). */
final class HitungMetrikPemasaran extends Command
{
    protected $signature = 'pemasaran:hitung-metrik {--tanggal= : Hari yang dihitung, bawaannya kemarin}';

    protected $description = 'Hitung metrik kampanye satu hari dan periksa alert growth';

    public function __construct(private readonly PemeriksaAlertPemasaran $pemeriksa)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $tanggal = $this->option('tanggal');

        HitungMetrikKampanye::dispatchSync(is_string($tanggal) && $tanggal !== '' ? $tanggal : null);

        $alert = $this->pemeriksa->periksa();
        $jumlah = count($alert);

        $this->info("Metrik kampanye dihitung, {$jumlah} alert growth baru dicatat.");

        return self::SUCCESS;
    }
}
