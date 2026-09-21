<?php

declare(strict_types=1);

namespace App\Domain\Aset\Domain\Enums;

enum TingkatKritisAset: string
{
    case Normal = 'Normal';
    case Tinggi = 'Tinggi';
    case SangatTinggi = 'SangatTinggi';
}
