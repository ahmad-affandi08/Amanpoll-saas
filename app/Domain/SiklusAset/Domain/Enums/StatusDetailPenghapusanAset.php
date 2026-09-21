<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Domain\Enums;

enum StatusDetailPenghapusanAset: string
{
    case Menunggu = 'Menunggu';
    case Selesai = 'Selesai';
    case Dibatalkan = 'Dibatalkan';
}
