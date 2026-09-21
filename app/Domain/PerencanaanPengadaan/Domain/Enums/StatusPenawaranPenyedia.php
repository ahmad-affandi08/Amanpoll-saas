<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Domain\Enums;

enum StatusPenawaranPenyedia: string
{
    case Diajukan = 'Diajukan';
    case Terpilih = 'Terpilih';
    case Ditolak = 'Ditolak';
}
