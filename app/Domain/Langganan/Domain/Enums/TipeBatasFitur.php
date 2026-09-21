<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Domain\Enums;

/**
 * Bentuk entitlement sebuah fitur (22.01/22.03).
 *
 * Boolean menjawab "boleh atau tidak", Angka menjawab "sampai berapa". Keduanya
 * dipisah karena pemeriksaannya berbeda: yang pertama diperiksa di gerbang
 * rute, yang kedua hanya dapat diperiksa saat baris baru hendak dibuat.
 */
enum TipeBatasFitur: string
{
    case Boolean = 'Boolean';
    case Angka = 'Angka';

    public function label(): string
    {
        return match ($this) {
            self::Boolean => 'Aktif/Nonaktif',
            self::Angka => 'Batas Jumlah',
        };
    }
}
