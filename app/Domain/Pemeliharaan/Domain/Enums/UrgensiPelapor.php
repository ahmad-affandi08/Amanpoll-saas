<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Domain\Enums;

/**
 * Seberapa mendesak menurut pelapor, dalam bahasa awam (Mode Lapangan, DESIGN §36.7
 * layar 06). Disimpan di `Keluhan.UsulanUrgensi` sebagai **usulan** (PRD 8.20):
 * koordinator melihatnya dan formulir prioritasnya terisi dari `prioritas()`, tetapi
 * `Prioritas` tetap hanya diubah pemegang `Keluhan.Kelola`.
 */
enum UrgensiPelapor: string
{
    case TidakBuruBuru = 'TidakBuruBuru';
    case MenggangguKerja = 'MenggangguKerja';
    case KerjaTerhenti = 'KerjaTerhenti';
    case Berbahaya = 'Berbahaya';

    /** Prioritas yang diusulkan urgensi ini. */
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
