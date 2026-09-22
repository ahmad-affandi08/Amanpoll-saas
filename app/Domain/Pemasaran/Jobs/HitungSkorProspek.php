<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Jobs;

use App\Domain\Pemasaran\Application\Services\PenghitungSkorProspek;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Prospek;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/** Menghitung ulang skor satu prospek (MARKETING.md 29). */
final class HitungSkorProspek implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly string $prospekId) {}

    public function handle(PenghitungSkorProspek $penghitung): void
    {
        $prospek = Prospek::query()->find($this->prospekId);

        // Prospek dapat terhapus antara pekerjaan diantrekan dan dijalankan.
        if ($prospek === null) {
            return;
        }

        $penghitung->hitungUlang($prospek);
    }

    public function uniqueId(): string
    {
        return $this->prospekId;
    }
}
