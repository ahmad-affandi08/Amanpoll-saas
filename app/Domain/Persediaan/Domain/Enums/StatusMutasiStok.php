<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Domain\Enums;

enum StatusMutasiStok: string
{
    case Draft = 'Draft';
    case Diposting = 'Diposting';
    case Dibatalkan = 'Dibatalkan';
}
