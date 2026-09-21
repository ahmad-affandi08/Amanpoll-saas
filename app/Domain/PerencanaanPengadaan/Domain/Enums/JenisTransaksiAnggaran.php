<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Domain\Enums;

enum JenisTransaksiAnggaran: string
{
    case Komitmen = 'Komitmen';
    case Realisasi = 'Realisasi';
    case PelepasanKomitmen = 'PelepasanKomitmen';
    case Penyesuaian = 'Penyesuaian';
}
