<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Enums;

/** Perjalanan satu sesi demo (MARKETING.md 11). */
enum StatusSesiDemo: string
{
    case Berjalan = 'Berjalan';
    case Selesai = 'Selesai';
    case Kedaluwarsa = 'Kedaluwarsa';
    case Dihentikan = 'Dihentikan';

    /** Sesi yang sudah final tidak menerima peristiwa baru dan tidak menghitung kuota serentak. */
    public function final(): bool
    {
        return $this !== self::Berjalan;
    }
}
