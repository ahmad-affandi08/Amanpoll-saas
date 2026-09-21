<?php

declare(strict_types=1);

namespace App\Domain\Penyedia\Domain\Enums;

enum StatusPenyedia: string
{
    case Aktif = 'Aktif';
    case Nonaktif = 'Nonaktif';
}
