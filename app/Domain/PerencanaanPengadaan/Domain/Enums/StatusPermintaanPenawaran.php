<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Domain\Enums;

enum StatusPermintaanPenawaran: string
{
    case Draft = 'Draft';
    case Dibuka = 'Dibuka';
    case Ditutup = 'Ditutup';
}
