<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Domain\ValueObjects;

use Carbon\CarbonImmutable;

/** Satu pembayaran yang sudah dipastikan berhasil oleh domain Langganan; domain lain tidak menilainya sendiri. */
final readonly class PembayaranTerkonfirmasi
{
    public function __construct(
        public string $pembayaranId,
        public string $organisasiId,
        public ?string $langgananId,
        public float $jumlah,
        public CarbonImmutable $dibayarPada,
    ) {}
}
