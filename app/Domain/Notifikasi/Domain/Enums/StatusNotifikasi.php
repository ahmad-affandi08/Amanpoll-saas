<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Domain\Enums;

enum StatusNotifikasi: string
{
    case Antri = 'Antri';
    case Terkirim = 'Terkirim';
    case Gagal = 'Gagal';
}
