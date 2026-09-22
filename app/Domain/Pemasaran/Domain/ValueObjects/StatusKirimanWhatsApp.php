<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\ValueObjects;

use App\Domain\Pemasaran\Domain\Enums\StatusPengirimanWhatsApp;

/** Status satu kiriman WhatsApp menurut penyedia (MARKETING.md 16). */
final readonly class StatusKirimanWhatsApp
{
    public function __construct(
        public string $idPesan,
        public StatusPengirimanWhatsApp $status,
        public ?string $keterangan = null,
    ) {}
}
