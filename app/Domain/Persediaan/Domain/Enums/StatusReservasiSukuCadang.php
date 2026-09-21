<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Domain\Enums;

enum StatusReservasiSukuCadang: string
{
    case Aktif = 'Aktif';
    case Dilepas = 'Dilepas';
    case Dipakai = 'Dipakai';
    case Kadaluarsa = 'Kadaluarsa';
}
