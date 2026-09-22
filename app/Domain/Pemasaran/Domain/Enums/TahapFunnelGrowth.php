<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Enums;

/** Tahap funnel utama dashboard growth (MARKETING.md 5). */
enum TahapFunnelGrowth: string
{
    case Visitor = 'Visitor';
    case Lead = 'Lead';
    case Demo = 'Demo';
    case Trial = 'Trial';
    case Activated = 'Activated';
    case Qualified = 'Qualified';
    case Paid = 'Paid';

    /** Tabel yang menjadi sumber angka tahap ini; itulah yang membuatnya dapat ditelusuri. */
    public function sumber(): string
    {
        return match ($this) {
            self::Visitor => 'SesiPengunjung',
            self::Lead => 'Prospek',
            self::Demo => 'EventPemasaran',
            self::Trial, self::Activated, self::Paid => 'Trial',
            self::Qualified => 'Prospek',
        };
    }
}
