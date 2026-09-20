<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Domain\Enums;

enum StatusPerintahKerja: string
{
    case Draf = 'Draf';
    case Terjadwal = 'Terjadwal';
    case Ditugaskan = 'Ditugaskan';
    case Diterima = 'Diterima';
    case Dikerjakan = 'Dikerjakan';
    case MenungguSukuCadang = 'MenungguSukuCadang';
    case MenungguPenyedia = 'MenungguPenyedia';
    case Dijeda = 'Dijeda';
    case MenungguVerifikasi = 'MenungguVerifikasi';
    case Selesai = 'Selesai';
    case Ditutup = 'Ditutup';
    case Dibatalkan = 'Dibatalkan';

    /** @return list<self> */
    public function tujuanYangDiizinkan(): array
    {
        return match ($this) {
            self::Draf => [self::Terjadwal, self::Ditugaskan, self::Dibatalkan],
            self::Terjadwal => [self::Ditugaskan, self::Dibatalkan],
            self::Ditugaskan => [self::Diterima, self::Dibatalkan],
            self::Diterima => [self::Dikerjakan, self::Dibatalkan],
            self::Dikerjakan => [self::MenungguSukuCadang, self::MenungguPenyedia, self::Dijeda, self::MenungguVerifikasi],
            self::MenungguSukuCadang, self::MenungguPenyedia, self::Dijeda => [self::Dikerjakan, self::Dibatalkan],
            self::MenungguVerifikasi => [self::Selesai, self::Dikerjakan],
            self::Selesai => [self::Ditutup, self::Dikerjakan],
            self::Ditutup => [self::Dikerjakan],
            self::Dibatalkan => [],
        };
    }

    public function dapatBeralihKe(self $tujuan): bool
    {
        return in_array($tujuan, $this->tujuanYangDiizinkan(), true);
    }

    public function dapatMencatatOperasional(): bool
    {
        return in_array($this, [self::Diterima, self::Dikerjakan, self::MenungguSukuCadang, self::MenungguPenyedia, self::Dijeda], true);
    }
}
