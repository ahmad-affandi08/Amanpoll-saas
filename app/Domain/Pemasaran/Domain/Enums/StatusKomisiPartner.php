<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Enums;

/** Status satu komisi dari lahir sampai terbayar (MARKETING.md 21). */
enum StatusKomisiPartner: string
{
    case Tertunda = 'Tertunda';
    case Disetujui = 'Disetujui';
    case Dibayar = 'Dibayar';
    case Dibatalkan = 'Dibatalkan';

    public function final(): bool
    {
        return $this === self::Dibayar || $this === self::Dibatalkan;
    }

    /** Hanya komisi yang sudah disetujui yang boleh ikut satu payout. */
    public function siapDibayar(): bool
    {
        return $this === self::Disetujui;
    }

    /** @return list<string> */
    public static function nilai(): array
    {
        return array_column(self::cases(), 'value');
    }
}
