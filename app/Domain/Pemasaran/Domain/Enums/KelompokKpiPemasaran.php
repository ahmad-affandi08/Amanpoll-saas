<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Enums;

/** Pengelompokan KPI dashboard growth (MARKETING.md 5). */
enum KelompokKpiPemasaran: string
{
    case Trafik = 'Trafik';
    case Konversi = 'Konversi';
    case Trial = 'Trial';
    case Revenue = 'Revenue';
    case Channel = 'Channel';
    case Referral = 'Referral';

    public function label(): string
    {
        return match ($this) {
            self::Trafik => 'Trafik',
            self::Konversi => 'Konversi',
            self::Trial => 'Trial',
            self::Revenue => 'Revenue',
            self::Channel => 'Channel',
            self::Referral => 'Referral',
        };
    }
}
