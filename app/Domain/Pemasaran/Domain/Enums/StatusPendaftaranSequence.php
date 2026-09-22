<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Enums;

/** Status keikutsertaan satu prospek dalam satu sequence (MARKETING.md 15). */
enum StatusPendaftaranSequence: string
{
    case Berjalan = 'Berjalan';
    case Selesai = 'Selesai';
    case Dihentikan = 'Dihentikan';
}
