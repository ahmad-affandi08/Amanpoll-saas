<?php

declare(strict_types=1);

namespace App\Domain\Aset\Domain\Enums;

enum JenisMeterAset: string
{
    case Kumulatif = 'Kumulatif';
    case NonKumulatif = 'NonKumulatif';
}
