<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Enums;

/** Jenis entri timeline yang ditulis manusia, bukan hasil pelacakan. */
enum JenisAktivitasProspek: string
{
    case Catatan = 'Catatan';
    case Panggilan = 'Panggilan';
    case Rapat = 'Rapat';
    case Email = 'Email';
    case WhatsApp = 'WhatsApp';
    case PerubahanTahap = 'PerubahanTahap';
}
