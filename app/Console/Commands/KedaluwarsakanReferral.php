<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Pemasaran\Application\Services\PelacakReferral;
use Illuminate\Console\Command;

/** Menutup referral yang lewat jendelanya tanpa berbuah (MARKETING.md 20). */
final class KedaluwarsakanReferral extends Command
{
    protected $signature = 'pemasaran:kedaluwarsakan-referral';

    protected $description = 'Tandai referral yang melewati KedaluwarsaPada tanpa pernah dibayar';

    public function __construct(private readonly PelacakReferral $pelacak)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $jumlah = $this->pelacak->kedaluwarsakan();
        $this->info("Menutup {$jumlah} referral yang kedaluwarsa.");

        return self::SUCCESS;
    }
}
