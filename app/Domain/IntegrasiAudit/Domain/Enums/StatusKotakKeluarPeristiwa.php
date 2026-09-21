<?php

declare(strict_types=1);

namespace App\Domain\IntegrasiAudit\Domain\Enums;

enum StatusKotakKeluarPeristiwa: string
{
    case Menunggu = 'Menunggu';
    case Diproses = 'Diproses';
    case Selesai = 'Selesai';
    case Gagal = 'Gagal';
}
