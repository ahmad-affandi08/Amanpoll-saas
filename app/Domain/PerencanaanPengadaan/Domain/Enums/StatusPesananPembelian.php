<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Domain\Enums;

enum StatusPesananPembelian: string
{
    case Draft = 'Draft';
    case MenungguPersetujuan = 'MenungguPersetujuan';
    case Disetujui = 'Disetujui';
    case Ditolak = 'Ditolak';
    case Dikirim = 'Dikirim';
    case DiterimaSebagian = 'DiterimaSebagian';
    case DiterimaPenuh = 'DiterimaPenuh';
}
