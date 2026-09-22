<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Enums;

/** Dari mana persetujuan pemasaran diperoleh (MARKETING.md 27). */
enum SumberKonsen: string
{
    case Formulir = 'Formulir';
    case Trial = 'Trial';
    case Impor = 'Impor';
    case Manual = 'Manual';
    case Api = 'Api';
    case Unsubscribe = 'Unsubscribe';
}
