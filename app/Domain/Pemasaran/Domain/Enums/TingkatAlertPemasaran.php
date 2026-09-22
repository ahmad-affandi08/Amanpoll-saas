<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Enums;

/** Seberapa mendesak satu alert growth (MARKETING.md 5). */
enum TingkatAlertPemasaran: string
{
    case Info = 'Info';
    case Peringatan = 'Peringatan';
    case Kritis = 'Kritis';
}
