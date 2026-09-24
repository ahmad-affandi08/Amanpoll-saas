<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\ValueObjects;

/** Satu pesan teks yang dikirim orang ke nomor WhatsApp kita (MARKETING.md 16). */
final readonly class PesanMasukWhatsApp
{
    public function __construct(
        public string $dari,
        public string $teks,
        public ?string $idPesan = null,
    ) {}
}
