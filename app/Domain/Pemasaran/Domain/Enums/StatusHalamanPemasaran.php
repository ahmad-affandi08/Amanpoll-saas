<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Enums;

/** Status halaman pemasaran (MARKETING.md 8). */
enum StatusHalamanPemasaran: string
{
    case Draf = 'Draf';
    case Review = 'Review';
    case Terjadwal = 'Terjadwal';
    case Terbit = 'Terbit';
    case Diarsipkan = 'Diarsipkan';

    /** @return list<self> */
    public function tujuanYangDiizinkan(): array
    {
        return match ($this) {
            self::Draf => [self::Review, self::Terjadwal, self::Terbit, self::Diarsipkan],
            self::Review => [self::Draf, self::Terjadwal, self::Terbit, self::Diarsipkan],
            self::Terjadwal => [self::Draf, self::Terbit, self::Diarsipkan],
            // Terbit ke Terbit adalah penerbitan ulang: draf baru menggantikan versi yang sedang tayang.
            self::Terbit => [self::Draf, self::Terbit, self::Diarsipkan],
            // Halaman yang diarsipkan kembali sebagai draf, tidak pernah langsung terbit.
            self::Diarsipkan => [self::Draf],
        };
    }

    public function bolehPindahKe(self $tujuan): bool
    {
        return in_array($tujuan, $this->tujuanYangDiizinkan(), true);
    }

    public function terlihatPublik(): bool
    {
        return $this === self::Terbit;
    }
}
