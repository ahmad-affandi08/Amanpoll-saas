<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\ValueObjects;

/** Satu pesan WhatsApp yang siap diserahkan ke penyedia (MARKETING.md 16, 28). */
final readonly class PesanWhatsApp
{
    public function __construct(
        public string $kepada,
        public string $kodeTemplate,
        public string $bahasa,
        public string $isiTeks,
        /** Id template di sisi penyedia; kosong berarti penyedia mencarinya sendiri dari kodenya. */
        public ?string $idTemplatePenyedia = null,
        /** Naskah template sebelum dirender; penyedia resmi memakainya untuk menyusun parameter template. */
        public ?string $naskahTemplate = null,
    ) {}
}
