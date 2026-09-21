<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Jobs;

use App\Domain\Kepatuhan\Application\Services\LayananSinkronisasiEksternal;
use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\SinkronisasiEksternal;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Menjalankan satu baris sinkronisasi di latar belakang (19.03). Percobaan
 * ulang diserahkan ke antrean; setelah percobaan terakhir habis, barisnya
 * ditandai gagal dengan pesan yang sudah disamarkan.
 */
final class JalankanSinkronisasiEksternal implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(private readonly string $sinkronisasiId) {}

    /**
     * Jeda menaik supaya sistem tujuan yang sedang sibuk tidak dibanjiri.
     *
     * @return list<int>
     */
    public function backoff(): array
    {
        return [60, 300];
    }

    public function handle(LayananSinkronisasiEksternal $layanan): void
    {
        $sinkronisasi = $this->ambil();

        if ($sinkronisasi instanceof SinkronisasiEksternal) {
            $layanan->jalankan($sinkronisasi);
        }
    }

    public function failed(Throwable $e): void
    {
        $sinkronisasi = $this->ambil();

        if ($sinkronisasi instanceof SinkronisasiEksternal) {
            app(LayananSinkronisasiEksternal::class)->gagalkan($sinkronisasi, $e->getMessage());
        }
    }

    private function ambil(): ?SinkronisasiEksternal
    {
        return SinkronisasiEksternal::query()
            ->withoutGlobalScopes()
            ->whereKey($this->sinkronisasiId)
            ->first();
    }
}
