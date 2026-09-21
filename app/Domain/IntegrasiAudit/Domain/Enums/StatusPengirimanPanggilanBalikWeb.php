<?php

declare(strict_types=1);

namespace App\Domain\IntegrasiAudit\Domain\Enums;

enum StatusPengirimanPanggilanBalikWeb: string
{
    case Antri = 'Antri';
    case Berhasil = 'Berhasil';
    case Gagal = 'Gagal';
    case GagalPermanen = 'GagalPermanen';
}
