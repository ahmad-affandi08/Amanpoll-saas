<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\ValueObjects;

use App\Domain\Pemasaran\Domain\Enums\ChannelSosial;

/** Satu posting yang siap diserahkan ke penyedia sosial (MARKETING.md 18, 28). */
final readonly class PosSosial
{
    public function __construct(
        public ChannelSosial $channel,
        public string $caption,
        public ?string $mediaUrl,
        /** Tautan tujuan yang sudah bertanda UTM; inilah yang membuat trafiknya terbaca di attribution. */
        public ?string $tautan,
        /** Kunci idempotensi yang dibawa ke penyedia bila ia mendukungnya. */
        public string $kunciIdempotensi,
    ) {}
}
