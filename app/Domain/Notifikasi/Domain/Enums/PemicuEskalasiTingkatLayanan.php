<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Domain\Enums;

enum PemicuEskalasiTingkatLayanan: string
{
    case Menjelang = 'Menjelang';
    case Terlewati = 'Terlewati';
}
