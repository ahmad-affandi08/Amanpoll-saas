<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\ValueObjects;

use App\Domain\Pemasaran\Domain\Enums\StatusPersetujuanTemplateWa;

/** Keputusan penyedia atas satu template WhatsApp (MARKETING.md 16, 28). */
final readonly class PersetujuanTemplateWa
{
    public function __construct(
        public StatusPersetujuanTemplateWa $status,
        public ?string $idTemplatePenyedia = null,
        public ?string $alasan = null,
    ) {}
}
