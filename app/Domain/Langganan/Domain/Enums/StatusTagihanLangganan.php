<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Domain\Enums;

enum StatusTagihanLangganan: string
{
    case BelumDibayar = 'BelumDibayar';
    case SebagianDibayar = 'SebagianDibayar';
    case Lunas = 'Lunas';
    case Dibatalkan = 'Dibatalkan';

    public function label(): string
    {
        return match ($this) {
            self::BelumDibayar => 'Belum Dibayar',
            self::SebagianDibayar => 'Sebagian Dibayar',
            self::Lunas => 'Lunas',
            self::Dibatalkan => 'Dibatalkan',
        };
    }

    public function masihDapatDibayar(): bool
    {
        return $this === self::BelumDibayar || $this === self::SebagianDibayar;
    }
}
