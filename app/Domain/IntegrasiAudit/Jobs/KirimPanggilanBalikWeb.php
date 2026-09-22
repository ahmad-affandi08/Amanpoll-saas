<?php

declare(strict_types=1);

namespace App\Domain\IntegrasiAudit\Jobs;

use App\Domain\IntegrasiAudit\Application\Services\LayananPanggilanBalikWeb;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\PengirimanPanggilanBalikWeb;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Mengirim satu panggilan balik web. */
final class KirimPanggilanBalikWeb implements ShouldQueue
{
    use Queueable;

    /**
     * Tangga percobaan ulang dimiliki `LayananPanggilanBalikWeb`, yang mencatat
     * `Percobaan` lalu menjadwalkan sendiri kiriman berikutnya. Queue yang ikut
     * mengulang akan menggandakan hitungan itu, jadi ia hanya menjalankan sekali.
     */
    public int $tries = 1;

    public function __construct(private readonly string $pengirimanId) {}

    public function handle(LayananPanggilanBalikWeb $layanan): void
    {
        $pengiriman = PengirimanPanggilanBalikWeb::query()
            ->withoutGlobalScopes()
            ->whereKey($this->pengirimanId)
            ->first();

        if ($pengiriman instanceof PengirimanPanggilanBalikWeb) {
            $layanan->kirim($pengiriman);
        }
    }
}
