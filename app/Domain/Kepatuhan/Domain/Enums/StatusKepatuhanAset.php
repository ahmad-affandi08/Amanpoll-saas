<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Domain\Enums;

enum StatusKepatuhanAset: string
{
    case BelumDiperiksa = 'BelumDiperiksa';
    case Patuh = 'Patuh';
    case TidakPatuh = 'TidakPatuh';
    case Kedaluwarsa = 'Kedaluwarsa';
}
