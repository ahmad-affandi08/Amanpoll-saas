<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Domain\Enums;

enum PrioritasKeluhan: string
{
    case Rendah = 'Rendah';
    case Normal = 'Normal';
    case Tinggi = 'Tinggi';
    case Kritis = 'Kritis';
}
