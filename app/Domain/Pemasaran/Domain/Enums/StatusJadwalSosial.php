<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Enums;

/** Nasib satu rencana penerbitan (MARKETING.md 18). */
enum StatusJadwalSosial: string
{
    case Menunggu = 'Menunggu';
    case Dijalankan = 'Dijalankan';
    case Dibatalkan = 'Dibatalkan';

    public function final(): bool
    {
        return $this !== self::Menunggu;
    }
}
