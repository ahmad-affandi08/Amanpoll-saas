<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Domain\Enums;

use Carbon\CarbonImmutable;

enum SiklusLangganan: string
{
    case Bulanan = 'Bulanan';
    case Tahunan = 'Tahunan';

    public function label(): string
    {
        return match ($this) {
            self::Bulanan => 'Bulanan',
            self::Tahunan => 'Tahunan',
        };
    }

    /** Akhir periode berikutnya dihitung dengan penambahan kalender, bukan penambahan hari. */
    public function akhirPeriodeSetelah(CarbonImmutable $mulai): CarbonImmutable
    {
        return match ($this) {
            self::Bulanan => $mulai->addMonthNoOverflow(),
            self::Tahunan => $mulai->addYearNoOverflow(),
        };
    }
}
