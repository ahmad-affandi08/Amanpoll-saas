<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Domain\Enums;

enum StatusKeluhan: string
{
    case Baru = 'Baru';
    case Ditinjau = 'Ditinjau';
    case Diterima = 'Diterima';
    case Diproses = 'Diproses';
    case Selesai = 'Selesai';
    case Ditutup = 'Ditutup';
    case Ditolak = 'Ditolak';
    case Dibatalkan = 'Dibatalkan';

    /**
     * @return list<self>
     */
    public function tujuanYangDiizinkan(): array
    {
        return match ($this) {
            self::Baru => [self::Ditinjau, self::Ditolak, self::Dibatalkan],
            self::Ditinjau => [self::Diterima, self::Ditolak, self::Dibatalkan],
            self::Diterima => [self::Diproses],
            self::Diproses => [self::Selesai],
            self::Selesai => [self::Ditutup, self::Diproses],
            self::Ditutup, self::Ditolak, self::Dibatalkan => [],
        };
    }

    public function dapatBeralihKe(self $tujuan): bool
    {
        return in_array($tujuan, $this->tujuanYangDiizinkan(), true);
    }

    public function final(): bool
    {
        return in_array($this, [self::Ditutup, self::Ditolak, self::Dibatalkan], true);
    }
}
