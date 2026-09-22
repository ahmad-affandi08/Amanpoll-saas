<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Enums;

/** Urgensi menggarap satu keyword (MARKETING.md 9). */
enum PrioritasKeyword: string
{
    case Tinggi = 'Tinggi';
    case Sedang = 'Sedang';
    case Rendah = 'Rendah';

    public function urutan(): int
    {
        return match ($this) {
            self::Tinggi => 0,
            self::Sedang => 1,
            self::Rendah => 2,
        };
    }
}
