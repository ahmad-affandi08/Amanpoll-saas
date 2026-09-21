<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Domain\Enums;

enum StatusAnggaran: string
{
    case Draft = 'Draft';
    case MenungguPersetujuan = 'MenungguPersetujuan';
    case Aktif = 'Aktif';
    case Ditolak = 'Ditolak';
    case Ditutup = 'Ditutup';
}
