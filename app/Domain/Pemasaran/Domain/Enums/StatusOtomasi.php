<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Enums;

/** Status satu versi otomasi (MARKETING.md 17). */
enum StatusOtomasi: string
{
    case Draf = 'Draf';
    case Aktif = 'Aktif';
    case Diarsipkan = 'Diarsipkan';

    /** @return list<self> */
    public function tujuanSah(): array
    {
        return match ($this) {
            self::Draf => [self::Aktif, self::Diarsipkan],
            // Versi aktif boleh diaktifkan ulang; menerbitkan versi yang sama dua kali bukan kesalahan.
            self::Aktif => [self::Aktif, self::Diarsipkan],
            self::Diarsipkan => [self::Draf],
        };
    }

    public function bolehPindahKe(self $tujuan): bool
    {
        return in_array($tujuan, $this->tujuanSah(), true);
    }
}
