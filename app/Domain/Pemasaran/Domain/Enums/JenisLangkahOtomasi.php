<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Enums;

/** Bentuk Trigger → Condition → Delay → Action (MARKETING.md 17). */
enum JenisLangkahOtomasi: string
{
    case Kondisi = 'Kondisi';
    case Jeda = 'Jeda';
    case Aksi = 'Aksi';
}
