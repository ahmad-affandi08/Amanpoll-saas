<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Enums;

use App\Domain\Langganan\Infrastructure\Persistence\Models\PaketLangganan;

/** Siklus harga yang dipresentasikan; angkanya tetap milik domain Langganan (MARKETING.md 19). */
enum SiklusHarga: string
{
    case Bulanan = 'Bulanan';
    case Tahunan = 'Tahunan';

    public function label(): string
    {
        return match ($this) {
            self::Bulanan => 'per bulan',
            self::Tahunan => 'per tahun',
        };
    }

    public function hargaDari(PaketLangganan $paket): float
    {
        return (float) match ($this) {
            self::Bulanan => $paket->HargaBulanan,
            self::Tahunan => $paket->HargaTahunan,
        };
    }
}
