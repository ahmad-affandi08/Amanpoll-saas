<?php

declare(strict_types=1);

namespace App\Domain\Aset\Domain\Enums;

enum JenisRelasiAset: string
{
    case Komponen = 'Komponen';
    case Terkait = 'Terkait';
}
