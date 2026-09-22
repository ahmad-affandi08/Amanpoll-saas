<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Jobs;

use App\Domain\Pemasaran\Application\Services\PengirimEmailPemasaran;
use App\Domain\Pemasaran\Domain\Enums\StatusPengirimanEmail;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\PengirimanEmailPemasaran;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/** Idempoten lewat status barisnya: hanya kiriman berstatus Terjadwal yang berangkat (MARKETING.md 15, 29). */
final class KirimEmailPemasaran implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * Penyedia email yang menolak sesaat butuh jeda sebelum dicoba lagi.
     *
     * @var list<int>
     */
    public array $backoff = [30, 120];

    public function __construct(public readonly string $pengirimanId) {}

    public function handle(PengirimEmailPemasaran $pengirim): void
    {
        $pengiriman = PengirimanEmailPemasaran::query()->find($this->pengirimanId);

        if ($pengiriman === null || $pengiriman->Status !== StatusPengirimanEmail::Terjadwal) {
            return;
        }

        $pengirim->kirim($pengiriman);
    }

    public function failed(?Throwable $galat): void
    {
        $pengiriman = PengirimanEmailPemasaran::query()->find($this->pengirimanId);

        if ($pengiriman === null || $pengiriman->Status !== StatusPengirimanEmail::Terjadwal) {
            return;
        }

        $pengiriman->Status = StatusPengirimanEmail::Gagal;
        $pengiriman->Galat = mb_substr((string) $galat?->getMessage(), 0, 500);
        $pengiriman->DiperbaruiStatusPada = CarbonImmutable::now();
        $pengiriman->save();
    }

    public function uniqueId(): string
    {
        return $this->pengirimanId;
    }
}
