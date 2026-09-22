<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Enums;

enum JenisPerangkat: string
{
    case Ponsel = 'Ponsel';
    case Tablet = 'Tablet';
    case Desktop = 'Desktop';
    case Lainnya = 'Lainnya';
}
