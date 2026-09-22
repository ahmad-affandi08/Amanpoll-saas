<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Enums;

/** Niat pencarian di balik satu keyword (MARKETING.md 9). */
enum IntentKeyword: string
{
    case Informational = 'INFORMATIONAL';
    case Commercial = 'COMMERCIAL';
    case Transactional = 'TRANSACTIONAL';
    case Navigational = 'NAVIGATIONAL';

    public function label(): string
    {
        return match ($this) {
            self::Informational => 'Mencari informasi',
            self::Commercial => 'Membandingkan pilihan',
            self::Transactional => 'Siap membeli',
            self::Navigational => 'Mencari merek tertentu',
        };
    }
}
