<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\ValueObjects;

use App\Domain\Pemasaran\Domain\Enums\StatusPengirimanEmail;

/** Status satu kiriman menurut penyedia (MARKETING.md 15). */
final readonly class StatusKirimanPenyedia
{
    public function __construct(
        public string $idPesan,
        public StatusPengirimanEmail $status,
        public ?string $keterangan = null,
    ) {}
}
