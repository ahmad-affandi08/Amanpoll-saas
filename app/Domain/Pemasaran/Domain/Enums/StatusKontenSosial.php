<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Enums;

/** Perjalanan satu distribusi konten sosial (MARKETING.md 18). */
enum StatusKontenSosial: string
{
    case Draf = 'Draf';
    case Review = 'Review';
    case Terjadwal = 'Terjadwal';
    case Diproses = 'Diproses';
    case Terbit = 'Terbit';
    case Gagal = 'Gagal';

    /** @return list<self> */
    public function tujuanSah(): array
    {
        return match ($this) {
            self::Draf => [self::Review],
            self::Review => [self::Draf, self::Terjadwal],
            self::Terjadwal => [self::Draf, self::Diproses],
            self::Diproses => [self::Terbit, self::Gagal],
            // Yang gagal dijadwalkan ulang lewat draf, supaya captionnya sempat diperbaiki.
            self::Gagal => [self::Draf],
            self::Terbit => [],
        };
    }

    public function bolehPindahKe(self $tujuan): bool
    {
        return in_array($tujuan, $this->tujuanSah(), true);
    }

    /** Sudah terbit tidak dapat dicabut dari sini; yang terbit ada di luar sistem ini. */
    public function final(): bool
    {
        return $this === self::Terbit;
    }

    public function bolehDiterbitkan(): bool
    {
        return $this === self::Terjadwal;
    }
}
