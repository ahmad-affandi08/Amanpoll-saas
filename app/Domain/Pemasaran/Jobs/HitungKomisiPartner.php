<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Jobs;

use App\Domain\Pemasaran\Application\Services\PenghitungKomisiPartner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/** Menghitung komisi satu pembayaran di luar siklus permintaan webhook (MARKETING.md 21, 26). */
final class HitungKomisiPartner implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(private readonly string $pembayaranId)
    {
        $this->onConnection('database');
    }

    public function handle(PenghitungKomisiPartner $penghitung): void
    {
        $penghitung->dariPembayaran($this->pembayaranId);
    }

    public function uniqueId(): string
    {
        return $this->pembayaranId;
    }
}
