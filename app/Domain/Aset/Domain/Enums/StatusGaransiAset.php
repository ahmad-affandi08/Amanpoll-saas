<?php

declare(strict_types=1);

namespace App\Domain\Aset\Domain\Enums;

enum StatusGaransiAset: string
{
    case Aktif = 'Aktif';
    case Berakhir = 'Berakhir';
    case Dibatalkan = 'Dibatalkan';
}
