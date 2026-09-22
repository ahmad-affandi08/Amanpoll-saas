<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Pemasaran\Application\Services\PenjadwalKontenSosial;
use App\Domain\Pemasaran\Jobs\TerbitkanKontenSosial;
use Illuminate\Console\Command;

/** Mengantrekan penerbitan sosial yang jadwalnya sudah tiba (MARKETING.md 18). */
final class TerbitkanJadwalSosial extends Command
{
    protected $signature = 'pemasaran:terbitkan-sosial {--batas=200 : Banyak jadwal yang diantrekan sekali jalan}';

    protected $description = 'Antrekan penerbitan konten sosial yang sudah jatuh tempo';

    public function __construct(private readonly PenjadwalKontenSosial $penjadwal)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $jatuhTempo = $this->penjadwal->jatuhTempo(batas: max((int) $this->option('batas'), 1));

        foreach ($jatuhTempo as $jadwal) {
            TerbitkanKontenSosial::dispatch($jadwal->Id);
        }

        $jumlah = count($jatuhTempo);
        $this->info("Mengantrekan {$jumlah} penerbitan konten sosial.");

        return self::SUCCESS;
    }
}
