<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Domain\Enums;

enum StatusPermintaanPembelian: string
{
    case Draft = 'Draft';
    case MenungguPersetujuan = 'MenungguPersetujuan';
    case Disetujui = 'Disetujui';
    case Ditolak = 'Ditolak';
}
