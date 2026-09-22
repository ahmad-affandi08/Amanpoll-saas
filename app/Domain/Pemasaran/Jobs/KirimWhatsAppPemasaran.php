<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Jobs;

use App\Domain\Pemasaran\Application\Services\PengirimWhatsAppPemasaran;
use App\Domain\Pemasaran\Domain\Enums\StatusPengirimanWhatsApp;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\PengirimanWhatsAppPemasaran;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/** Idempoten lewat status barisnya: hanya kiriman berstatus Terjadwal yang berangkat (MARKETING.md 16, 29). */
final class KirimWhatsAppPemasaran implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * Penyedia WhatsApp membatasi laju; mengulang tanpa jeda memperburuknya.
     *
     * @var list<int>
     */
    public array $backoff = [30, 120];

    public function __construct(public readonly string $pengirimanId) {}

    public function handle(PengirimWhatsAppPemasaran $pengirim): void
    {
        $pengiriman = PengirimanWhatsAppPemasaran::query()->find($this->pengirimanId);

        if ($pengiriman === null || $pengiriman->Status !== StatusPengirimanWhatsApp::Terjadwal) {
            return;
        }

        $pengirim->kirim($pengiriman);
    }

    public function failed(?Throwable $galat): void
    {
        $pengiriman = PengirimanWhatsAppPemasaran::query()->find($this->pengirimanId);

        if ($pengiriman === null || $pengiriman->Status !== StatusPengirimanWhatsApp::Terjadwal) {
            return;
        }

        $pengiriman->Status = StatusPengirimanWhatsApp::Gagal;
        $pengiriman->Galat = mb_substr((string) $galat?->getMessage(), 0, 500);
        $pengiriman->DiperbaruiStatusPada = CarbonImmutable::now();
        $pengiriman->save();
    }

    public function uniqueId(): string
    {
        return $this->pengirimanId;
    }
}
