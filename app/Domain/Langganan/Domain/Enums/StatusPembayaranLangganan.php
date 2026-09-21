<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Domain\Enums;

enum StatusPembayaranLangganan: string
{
    case Menunggu = 'Menunggu';
    case Berhasil = 'Berhasil';
    case Gagal = 'Gagal';
    case Dikembalikan = 'Dikembalikan';

    public function label(): string
    {
        return match ($this) {
            self::Menunggu => 'Menunggu',
            self::Berhasil => 'Berhasil',
            self::Gagal => 'Gagal',
            self::Dikembalikan => 'Dikembalikan',
        };
    }

    /** Hanya pembayaran berhasil yang mengurangi sisa tagihan. */
    public function mengurangiTagihan(): bool
    {
        return $this === self::Berhasil;
    }
}
