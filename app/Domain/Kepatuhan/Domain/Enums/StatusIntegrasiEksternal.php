<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Domain\Enums;

enum StatusIntegrasiEksternal: string
{
    case Aktif = 'Aktif';
    case Nonaktif = 'Nonaktif';
    case Bermasalah = 'Bermasalah';
}
