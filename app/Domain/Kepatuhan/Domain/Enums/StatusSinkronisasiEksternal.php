<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Domain\Enums;

enum StatusSinkronisasiEksternal: string
{
    case Diproses = 'Diproses';
    case Berhasil = 'Berhasil';
    case Sebagian = 'Sebagian';
    case Gagal = 'Gagal';
}
