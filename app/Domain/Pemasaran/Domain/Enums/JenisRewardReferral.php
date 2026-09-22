<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Enums;

/** Bentuk imbalan yang dijanjikan satu program referral (MARKETING.md 20). */
enum JenisRewardReferral: string
{
    case Perpanjangan = 'Perpanjangan';
    case Kredit = 'Kredit';
    case Kupon = 'Kupon';
    case Kustom = 'Kustom';

    /** Imbalan yang diselesaikan manusia di luar sistem, jadi tidak menuntut dukungan Langganan. */
    public function manual(): bool
    {
        return $this === self::Kustom;
    }
}
