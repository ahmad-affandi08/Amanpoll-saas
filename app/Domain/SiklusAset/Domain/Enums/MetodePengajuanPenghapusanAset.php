<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Domain\Enums;

enum MetodePengajuanPenghapusanAset: string
{
    case Dijual = 'Dijual';
    case Dimusnahkan = 'Dimusnahkan';
    case Hibah = 'Hibah';
    case Hilang = 'Hilang';
    case Lainnya = 'Lainnya';
}
