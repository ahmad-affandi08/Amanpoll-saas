<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Enums;

/** Perjalanan satu kampanye (MARKETING.md 13). */
enum StatusKampanye: string
{
    case Draf = 'Draf';
    case Siap = 'Siap';
    case Aktif = 'Aktif';
    case Dijeda = 'Dijeda';
    case Selesai = 'Selesai';
    case Diarsipkan = 'Diarsipkan';

    /** @return list<self> */
    public function tujuanSah(): array
    {
        return match ($this) {
            self::Draf => [self::Siap, self::Diarsipkan],
            self::Siap => [self::Draf, self::Aktif, self::Diarsipkan],
            self::Aktif => [self::Dijeda, self::Selesai],
            self::Dijeda => [self::Aktif, self::Selesai],
            // Kampanye yang sudah selesai masih boleh diarsipkan, tetapi tidak dinyalakan lagi.
            self::Selesai => [self::Diarsipkan],
            self::Diarsipkan => [self::Draf],
        };
    }

    public function bolehPindahKe(self $tujuan): bool
    {
        return in_array($tujuan, $this->tujuanSah(), true);
    }

    /** Kampanye yang sedang membelanjakan uang dan menerima trafik. */
    public function berjalan(): bool
    {
        return $this === self::Aktif;
    }
}
