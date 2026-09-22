<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Enums;

/** Hasil satu langkah yang dicatat di log eksekusi (MARKETING.md 17). */
enum HasilLangkahOtomasi: string
{
    case Sukses = 'Sukses';
    case KondisiTidakTerpenuhi = 'KondisiTidakTerpenuhi';
    case Ditunda = 'Ditunda';
    case Dilewati = 'Dilewati';
    case Gagal = 'Gagal';
}
