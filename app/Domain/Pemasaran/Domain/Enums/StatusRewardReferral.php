<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Enums;

/** Status satu imbalan referral (MARKETING.md 20). */
enum StatusRewardReferral: string
{
    case Tertunda = 'Tertunda';
    case Diberikan = 'Diberikan';
    case Gagal = 'Gagal';
    case Dibatalkan = 'Dibatalkan';

    public function final(): bool
    {
        return in_array($this, [self::Diberikan, self::Dibatalkan], true);
    }
}
