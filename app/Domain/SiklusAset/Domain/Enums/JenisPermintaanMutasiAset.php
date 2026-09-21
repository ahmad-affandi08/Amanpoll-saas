<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Domain\Enums;

enum JenisPermintaanMutasiAset: string
{
    case AntarLokasi = 'AntarLokasi';
    case AntarUnit = 'AntarUnit';
    case Peminjaman = 'Peminjaman';
    case Pengembalian = 'Pengembalian';
}
