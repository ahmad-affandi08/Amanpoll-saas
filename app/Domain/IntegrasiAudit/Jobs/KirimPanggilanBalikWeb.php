<?php

declare(strict_types=1);

namespace App\Domain\IntegrasiAudit\Jobs;

use App\Domain\IntegrasiAudit\Application\Services\LayananPanggilanBalikWeb;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\PengirimanPanggilanBalikWeb;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Mengirim satu panggilan balik web. Percobaan ulang dikelola oleh layanan
 * lewat kolom JadwalCobaLagiPada, bukan oleh retry queue, supaya jejak tiap
 * percobaan tetap terlihat pada log pengiriman.
 */
final class KirimPanggilanBalikWeb implements ShouldQueue
{
    use Queueable;

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
