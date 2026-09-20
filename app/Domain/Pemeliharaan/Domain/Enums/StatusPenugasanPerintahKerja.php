<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Domain\Enums;

enum StatusPenugasanPerintahKerja: string
{
    case Ditugaskan = 'Ditugaskan';
    case Diterima = 'Diterima';
    case Ditolak = 'Ditolak';
    case Diganti = 'Diganti';
    case Selesai = 'Selesai';
}
