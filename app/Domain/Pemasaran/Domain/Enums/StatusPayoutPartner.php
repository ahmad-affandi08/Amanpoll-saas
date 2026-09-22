<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Enums;

/** Status satu payout partner; transfernya sendiri terjadi di luar aplikasi (MARKETING.md 21). */
enum StatusPayoutPartner: string
{
    case Draf = 'Draf';
    case Diproses = 'Diproses';
    case Dibayar = 'Dibayar';
    case Gagal = 'Gagal';

    public function final(): bool
    {
        return $this === self::Dibayar;
    }

    /** @return list<string> */
    public static function nilai(): array
    {
        return array_column(self::cases(), 'value');
    }
}
