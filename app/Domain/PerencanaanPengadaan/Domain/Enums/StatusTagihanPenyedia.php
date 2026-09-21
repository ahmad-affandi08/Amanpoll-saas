<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Domain\Enums;

enum StatusTagihanPenyedia: string
{
    case BelumDibayar = 'BelumDibayar';
    case DibayarSebagian = 'DibayarSebagian';
    case Dibayar = 'Dibayar';
}
