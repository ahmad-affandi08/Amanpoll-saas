<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Domain\Enums;

enum StatusPengajuanPenghapusanAset: string
{
    case Draft = 'Draft';
    case Menunggu = 'Menunggu';
    case Disetujui = 'Disetujui';
    case Ditolak = 'Ditolak';
    case Dibatalkan = 'Dibatalkan';
    case Selesai = 'Selesai';
}
