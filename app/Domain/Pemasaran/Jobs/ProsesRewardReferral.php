<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Jobs;

use App\Domain\Pemasaran\Application\Services\PenghitungRewardReferral;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\RewardReferral;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/** Memberikan satu imbalan referral; status akhirnya yang menahan pemberian kedua. */
final class ProsesRewardReferral implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(private readonly string $rewardId)
    {
        $this->onConnection('database');
    }

    public function handle(PenghitungRewardReferral $penghitung): void
    {
        $reward = RewardReferral::query()->find($this->rewardId);

        if ($reward !== null && ! $reward->Status->final()) {
            $penghitung->berikan($reward);
        }
    }

    public function uniqueId(): string
    {
        return $this->rewardId;
    }
}
