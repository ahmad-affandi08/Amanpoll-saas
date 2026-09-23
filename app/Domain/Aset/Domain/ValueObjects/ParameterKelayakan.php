<?php

declare(strict_types=1);

namespace App\Domain\Aset\Domain\ValueObjects;

use App\Domain\Platform\Infrastructure\Persistence\Models\KonfigurasiOrganisasi;

/**
 * Angka acuan perhitungan AIC dan MMEL, disimpan per organisasi.
 *
 * Ketiganya tidak seragam antar rumah sakit: laju inflasi mengikuti asumsi
 * anggaran masing-masing, faktor MEL berbeda antar kelas alat dan antar acuan
 * yang dipakai, dan persentase anggaran pemeliharaan terhadap AIC adalah
 * kebijakan. Karena itu semuanya parameter, bukan angka mati di kode.
 *
 * Nilai bawaannya titik mulai yang wajar, bukan standar resmi. Sesuaikan
 * dengan acuan yang berlaku di rumah sakit sebelum angkanya dipakai
 * memutuskan penggantian alat.
 */
final class ParameterKelayakan
{
    public const KUNCI_INFLASI = 'Kelayakan.LajuInflasi';

    public const KUNCI_FAKTOR_MEL = 'Kelayakan.FaktorMel';

    public const KUNCI_PERSEN_PEMELIHARAAN = 'Kelayakan.PersenPemeliharaanAic';

    public const BAWAAN_INFLASI = 0.05;

    public const BAWAAN_FAKTOR_MEL = 0.60;

    public const BAWAAN_PERSEN_PEMELIHARAAN = 0.05;

    private function __construct(
        public readonly float $lajuInflasi,
        public readonly float $faktorMel,
        public readonly float $persenPemeliharaanAic,
    ) {}

    public static function bawaan(): self
    {
        return new self(self::BAWAAN_INFLASI, self::BAWAAN_FAKTOR_MEL, self::BAWAAN_PERSEN_PEMELIHARAAN);
    }

    /** Dibaca sekali lalu dioper, karena satu daftar aset memanggilnya ratusan kali. */
    public static function dariKonfigurasi(): self
    {
        $nilai = KonfigurasiOrganisasi::query()
            ->whereIn('Kunci', [self::KUNCI_INFLASI, self::KUNCI_FAKTOR_MEL, self::KUNCI_PERSEN_PEMELIHARAAN])
            ->pluck('Nilai', 'Kunci');

        return new self(
            self::angka($nilai[self::KUNCI_INFLASI] ?? null, self::BAWAAN_INFLASI),
            self::angka($nilai[self::KUNCI_FAKTOR_MEL] ?? null, self::BAWAAN_FAKTOR_MEL),
            self::angka($nilai[self::KUNCI_PERSEN_PEMELIHARAAN] ?? null, self::BAWAAN_PERSEN_PEMELIHARAAN),
        );
    }

    /** Nilai rusak dikembalikan ke bawaan, bukan dijadikan nol yang diam-diam mematikan perhitungannya. */
    private static function angka(mixed $mentah, float $bawaan): float
    {
        if (! is_string($mentah) && ! is_numeric($mentah)) {
            return $bawaan;
        }

        return is_numeric($mentah) ? (float) $mentah : $bawaan;
    }
}
