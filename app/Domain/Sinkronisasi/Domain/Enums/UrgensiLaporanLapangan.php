<?php

declare(strict_types=1);

namespace App\Domain\Sinkronisasi\Domain\Enums;

use App\Domain\Pemeliharaan\Domain\Enums\PrioritasKeluhan;

/**
 * Tingkat urgensi dalam bahasa awam di layar "Apa masalahnya?" (DESIGN §36.7 layar 06),
 * dipetakan ke `PrioritasKeluhan` milik Pemeliharaan.
 */
enum UrgensiLaporanLapangan: string
{
    case TidakBuruBuru = 'TidakBuruBuru';
    case MenggangguKerja = 'MenggangguKerja';
    case KerjaTerhenti = 'KerjaTerhenti';
    case Berbahaya = 'Berbahaya';

    public function prioritas(): PrioritasKeluhan
    {
        return match ($this) {
            self::TidakBuruBuru => PrioritasKeluhan::Rendah,
            self::MenggangguKerja => PrioritasKeluhan::Normal,
            self::KerjaTerhenti => PrioritasKeluhan::Tinggi,
            self::Berbahaya => PrioritasKeluhan::Kritis,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::TidakBuruBuru => 'Tidak buru-buru',
            self::MenggangguKerja => 'Mengganggu kerja',
            self::KerjaTerhenti => 'Kerja terhenti',
            self::Berbahaya => 'Berbahaya',
        };
    }
}
