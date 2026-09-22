<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\ValueObjects;

/** Satu email siap kirim, sudah dirender (MARKETING.md 15). */
final readonly class PesanEmail
{
    public function __construct(
        public string $kepada,
        public string $subjek,
        public string $isiHtml,
        public ?string $isiTeks = null,
        public ?string $urlBerhentiLangganan = null,
    ) {}
}
