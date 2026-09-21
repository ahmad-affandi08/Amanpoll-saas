<?php

declare(strict_types=1);

namespace App\Domain\Aset\Domain\Enums;

enum KondisiAset: string
{
    case Baik = 'Baik';
    case PerluPerhatian = 'PerluPerhatian';
    case Rusak = 'Rusak';
}
