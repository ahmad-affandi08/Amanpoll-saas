<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Domain\Enums;

/** Pemicu rencana preventif: tanggal, pemakaian meter, atau mana yang lebih dulu tercapai. */
enum StrategiJadwalPreventif: string
{
    case Interval = 'Interval';
    case PenggunaanMeter = 'PenggunaanMeter';
    case Kombinasi = 'Kombinasi';

    public function memakaiKalender(): bool
    {
        return $this !== self::PenggunaanMeter;
    }

    public function memakaiMeter(): bool
    {
        return $this !== self::Interval;
    }

    /** Nilai tersimpan yang tidak dikenal diperlakukan sebagai interval kalender, perilaku lamanya. */
    public static function dari(?string $nilai): self
    {
        return self::tryFrom((string) $nilai) ?? self::Interval;
    }
}
