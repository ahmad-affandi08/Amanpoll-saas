<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Domain\Enums;

enum StatusRencanaPengadaan: string
{
    case Draft = 'Draft';
    case Direncanakan = 'Direncanakan';
    case Dibatalkan = 'Dibatalkan';
}
