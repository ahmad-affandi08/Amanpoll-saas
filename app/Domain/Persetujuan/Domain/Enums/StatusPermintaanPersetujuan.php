<?php

declare(strict_types=1);

namespace App\Domain\Persetujuan\Domain\Enums;

enum StatusPermintaanPersetujuan: string
{
    case Menunggu = 'Menunggu';
    case Disetujui = 'Disetujui';
    case Ditolak = 'Ditolak';
    case Dibatalkan = 'Dibatalkan';
}
