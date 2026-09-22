<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Pemasaran\Application\Services\PenghitungRewardReferral;
use App\Domain\Pemasaran\Jobs\ProsesRewardReferral;
use Illuminate\Console\Command;

/** Mengantrekan imbalan referral yang masih terutang (MARKETING.md 20). */
final class ProsesAntrianRewardReferral extends Command
{
    protected $signature = 'pemasaran:proses-reward-referral';

    protected $description = 'Antrekan imbalan referral yang belum diberikan';

    public function __construct(private readonly PenghitungRewardReferral $penghitung)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $tertunda = $this->penghitung->tertunda();

        foreach ($tertunda as $reward) {
            ProsesRewardReferral::dispatch($reward->Id);
        }

        $jumlah = count($tertunda);
        $this->info("Mengantrekan {$jumlah} imbalan referral.");

        return self::SUCCESS;
    }
}
