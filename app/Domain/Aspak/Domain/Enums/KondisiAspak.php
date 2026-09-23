<?php

declare(strict_types=1);

namespace App\Domain\Aspak\Domain\Enums;

use App\Domain\Aset\Domain\Enums\KondisiAset;

/**
 * Istilah kondisi yang dikenal ASPAK.
 *
 * Kondisi internal kita hanya tiga tingkat, sedangkan ASPAK memisahkan rusak
 * ringan dari rusak berat. Pemetaannya sengaja konservatif: `Rusak` di sistem
 * kita dilaporkan sebagai rusak berat, karena melaporkan alat rusak sebagai
 * rusak ringan akan menggelembungkan angka kesiapan alat rumah sakit.
 */
enum KondisiAspak: string
{
    case Baik = 'Baik';

    case RusakRingan = 'Rusak Ringan';

    case RusakBerat = 'Rusak Berat';

    public static function dariKondisiAset(?string $kondisi): self
    {
        return match ($kondisi) {
            KondisiAset::Baik->value => self::Baik,
            KondisiAset::PerluPerhatian->value => self::RusakRingan,
            KondisiAset::Rusak->value => self::RusakBerat,
            default => self::Baik,
        };
    }
}
