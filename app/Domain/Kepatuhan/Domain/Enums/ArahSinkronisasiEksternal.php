<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Domain\Enums;

enum ArahSinkronisasiEksternal: string
{
    case Tarik = 'Tarik';
    case Dorong = 'Dorong';
}
