<?php

declare(strict_types=1);

namespace App\Domain\Kontrak\Domain\Enums;

enum StatusKontrak: string
{
    case Aktif = 'Aktif';
    case Berakhir = 'Berakhir';
    case Dibatalkan = 'Dibatalkan';
}
