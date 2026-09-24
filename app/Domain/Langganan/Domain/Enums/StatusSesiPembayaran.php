<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Domain\Enums;

use App\Domain\Langganan\Domain\ValueObjects\PeristiwaPembayaran;

/** Keadaan satu sesi bayar di payment gateway (PRD 8.23). */
enum StatusSesiPembayaran: string
{
    case Menunggu = 'Menunggu';
    case Dibayar = 'Dibayar';
    case Gagal = 'Gagal';
    case Kedaluwarsa = 'Kedaluwarsa';

    public function label(): string
    {
        return match ($this) {
            self::Menunggu => 'Menunggu Pembayaran',
            self::Dibayar => 'Dibayar',
            self::Gagal => 'Gagal',
            self::Kedaluwarsa => 'Kedaluwarsa',
        };
    }

    public static function dariPeristiwa(PeristiwaPembayaran $peristiwa): self
    {
        return match ($peristiwa->status) {
            StatusPembayaranLangganan::Berhasil => self::Dibayar,
            StatusPembayaranLangganan::Menunggu => self::Menunggu,
            StatusPembayaranLangganan::Gagal => $peristiwa->kedaluwarsa ? self::Kedaluwarsa : self::Gagal,
            StatusPembayaranLangganan::Dikembalikan => self::Gagal,
        };
    }
}
