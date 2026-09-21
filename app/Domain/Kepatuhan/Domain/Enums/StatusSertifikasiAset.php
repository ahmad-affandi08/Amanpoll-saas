<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Domain\Enums;

enum StatusSertifikasiAset: string
{
    case Aktif = 'Aktif';
    case Kedaluwarsa = 'Kedaluwarsa';
    case Dicabut = 'Dicabut';
}
