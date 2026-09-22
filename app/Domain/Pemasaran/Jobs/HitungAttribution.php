<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Jobs;

use App\Domain\Pemasaran\Application\Services\PenyusunUlangAttribution;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/** Menyusun ulang attribution satu pengunjung dari sesi-sesinya (MARKETING.md 29). */
final class HitungAttribution implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly string $pengenalPengunjung) {}

    public function handle(PenyusunUlangAttribution $penyusun): void
    {
        $penyusun->susunUlang($this->pengenalPengunjung);
    }

    /** Satu pengunjung tidak perlu disusun ulang berkali-kali sekaligus. */
    public function uniqueId(): string
    {
        return $this->pengenalPengunjung;
    }
}
