<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Enums;

/** Permintaan penghapusan atau anonimisasi data prospek (MARKETING.md 27). */
enum JenisPermintaanData: string
{
    case Penghapusan = 'Penghapusan';
    case Anonimisasi = 'Anonimisasi';
}
