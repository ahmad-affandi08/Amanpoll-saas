<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Domain\Enums;

enum JenisMutasiStok: string
{
    case Penerimaan = 'Penerimaan';
    case Pengeluaran = 'Pengeluaran';
    case Transfer = 'Transfer';
    case Adjustment = 'Adjustment';
    case Return = 'Return';
}
