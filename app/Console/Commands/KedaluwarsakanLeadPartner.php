<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Pemasaran\Application\Services\PelacakLeadPartner;
use Illuminate\Console\Command;

/** Menutup lead partner yang lewat jendela atribusinya tanpa berbuah (MARKETING.md 21). */
final class KedaluwarsakanLeadPartner extends Command
{
    protected $signature = 'pemasaran:kedaluwarsakan-lead-partner';

    protected $description = 'Tolak lead partner yang melewati KedaluwarsaPada tanpa pernah menjadi trial';

    public function __construct(private readonly PelacakLeadPartner $pelacak)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $jumlah = $this->pelacak->kedaluwarsakan();
        $this->info("Menutup {$jumlah} lead partner yang kedaluwarsa.");

        return self::SUCCESS;
    }
}
