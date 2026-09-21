<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Domain\Enums;

enum StatusUsulanAset: string
{
    case Draft = 'Draft';
    case Diajukan = 'Diajukan';
    case MenungguPersetujuan = 'MenungguPersetujuan';
    case Disetujui = 'Disetujui';
    case Ditolak = 'Ditolak';
}
