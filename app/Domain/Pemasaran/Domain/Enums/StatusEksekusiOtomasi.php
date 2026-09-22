<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Enums;

/** Status satu eksekusi otomasi (MARKETING.md 17). */
enum StatusEksekusiOtomasi: string
{
    case Berjalan = 'Berjalan';
    case Tertunda = 'Tertunda';
    case Selesai = 'Selesai';
    case BerhentiKondisi = 'BerhentiKondisi';
    case Gagal = 'Gagal';
    case GagalPermanen = 'GagalPermanen';

    /** Status akhir yang tidak akan diproses lagi. */
    public function final(): bool
    {
        return in_array($this, [self::Selesai, self::BerhentiKondisi, self::GagalPermanen], true);
    }

    /** Status yang masih menunggu giliran diproses pekerja. */
    public function siapDiproses(): bool
    {
        return in_array($this, [self::Berjalan, self::Tertunda, self::Gagal], true);
    }
}
