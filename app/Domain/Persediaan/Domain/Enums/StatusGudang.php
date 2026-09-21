<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Domain\Enums;

enum StatusGudang: string
{
    case Aktif = 'Aktif';
    case Nonaktif = 'Nonaktif';
}
