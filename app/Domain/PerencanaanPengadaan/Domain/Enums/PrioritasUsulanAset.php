<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Domain\Enums;

enum PrioritasUsulanAset: string
{
    case Rendah = 'Rendah';
    case Normal = 'Normal';
    case Tinggi = 'Tinggi';
    case Kritis = 'Kritis';
}
