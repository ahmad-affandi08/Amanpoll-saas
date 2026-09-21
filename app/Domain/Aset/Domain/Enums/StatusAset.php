<?php

declare(strict_types=1);

namespace App\Domain\Aset\Domain\Enums;

enum StatusAset: string
{
    case Aktif = 'Aktif';
    case Nonaktif = 'Nonaktif';
    case Dipinjam = 'Dipinjam';
    case Rusak = 'Rusak';
    case Diarsipkan = 'Diarsipkan';
}
