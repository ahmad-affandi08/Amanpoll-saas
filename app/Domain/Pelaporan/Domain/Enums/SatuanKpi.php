<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Domain\Enums;

/**
 * Satuan nilai KPI. Dipakai klien untuk memformat angka tanpa menebak, dan
 * dipakai ekspor untuk menulis kolom dengan tipe yang benar.
 */
enum SatuanKpi: string
{
    case Jumlah = 'Jumlah';
    case Persen = 'Persen';
    case Menit = 'Menit';
    case Jam = 'Jam';
    case Hari = 'Hari';
    case Uang = 'Uang';

    /** Jumlah angka di belakang koma yang wajar untuk satuan ini. */
    public function desimal(): int
    {
        return match ($this) {
            self::Jumlah => 0,
            self::Persen, self::Jam, self::Hari => 1,
            self::Menit => 0,
            self::Uang => 2,
        };
    }
}
