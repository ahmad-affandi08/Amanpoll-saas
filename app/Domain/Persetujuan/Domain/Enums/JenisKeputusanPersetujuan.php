<?php

declare(strict_types=1);

namespace App\Domain\Persetujuan\Domain\Enums;

enum JenisKeputusanPersetujuan: string
{
    case Disetujui = 'Disetujui';
    case Ditolak = 'Ditolak';
}
