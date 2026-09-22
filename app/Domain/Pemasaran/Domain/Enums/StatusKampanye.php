<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Enums;

enum StatusKampanye: string
{
    case Draf = 'Draf';
    case Siap = 'Siap';
    case Aktif = 'Aktif';
    case Dijeda = 'Dijeda';
    case Selesai = 'Selesai';
    case Diarsipkan = 'Diarsipkan';
}
