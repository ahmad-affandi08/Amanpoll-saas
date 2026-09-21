<?php

declare(strict_types=1);

namespace App\Domain\Aset\Domain\Enums;

enum JenisRiwayatLokasiAset: string
{
    case Registrasi = 'Registrasi';
    case Manual = 'Manual';
    case Mutasi = 'Mutasi';
}
