<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Enums;

/** Perjalanan satu eksperimen (MARKETING.md 22). */
enum StatusEksperimen: string
{
    case Draf = 'Draf';
    case Aktif = 'Aktif';
    case Dijeda = 'Dijeda';
    case Selesai = 'Selesai';

    /** @return list<self> */
    public function tujuanSah(): array
    {
        return match ($this) {
            self::Draf => [self::Aktif],
            self::Aktif => [self::Dijeda, self::Selesai],
            self::Dijeda => [self::Aktif, self::Selesai],
            self::Selesai => [],
        };
    }

    public function bolehPindahKe(self $tujuan): bool
    {
        return in_array($tujuan, $this->tujuanSah(), true);
    }

    /** Hanya eksperimen yang sedang berjalan yang menetapkan varian kepada pengunjung baru. */
    public function menerimaPeserta(): bool
    {
        return $this === self::Aktif;
    }
}
