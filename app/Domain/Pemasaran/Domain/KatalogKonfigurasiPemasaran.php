<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain;

/**
 * Setelan domain Pemasaran beserta nilai bawaannya (MARKETING.md 30).
 *
 * Katalog ini yang menjadi sumber kebenaran, bukan tabelnya: setelan yang belum
 * pernah disentuh tetap punya nilai yang masuk akal, dan tidak mungkin ada kode
 * yang membaca kunci tanpa definisi.
 */
final class KatalogKonfigurasiPemasaran
{
    public const TRIAL_HARI = 'trial.hari';

    public const TRIAL_PERPANJANGAN_MAKS_HARI = 'trial.perpanjangan_maks_hari';

    public const SKOR_ATURAN = 'skor.aturan';

    public const SKOR_AMBANG_QUALIFIED = 'skor.ambang_qualified';

    public const ATTRIBUTION_JENDELA_HARI = 'attribution.jendela_hari';

    public const CONSENT_VERSI_KEBIJAKAN = 'consent.versi_kebijakan';

    public const EMAIL_CAP_HARIAN = 'email.cap_harian';

    public const OTOMASI_CAP_EKSEKUSI = 'otomasi.cap_eksekusi';

    public const OTOMASI_CAP_PERCOBAAN = 'otomasi.cap_percobaan';

    public const REFERRAL_HARI_KEDALUWARSA = 'referral.hari_kedaluwarsa';

    /**
     * @return array<string, array{bawaan: mixed, keterangan: string}>
     */
    public static function semua(): array
    {
        return [
            self::TRIAL_HARI => [
                'bawaan' => 14,
                'keterangan' => 'Lama masa uji coba yang ditawarkan di situs publik.',
            ],
            self::TRIAL_PERPANJANGAN_MAKS_HARI => [
                'bawaan' => 14,
                'keterangan' => 'Batas perpanjangan trial yang boleh diberikan otomasi.',
            ],
            self::SKOR_ATURAN => [
                // Angka skor tidak pernah di-hard-code di kode program
                // (MARKETING.md 5.4); inilah tempatnya diubah.
                'bawaan' => [
                    'HargaDilihat' => 5,
                    'DemoDimulai' => 8,
                    'FormulirDikirim' => 10,
                    'TrialDimulai' => 15,
                    'AsetPertamaDibuat' => 20,
                    'PenggunaPertamaDiundang' => 15,
                    'PerintahKerjaPertamaDibuat' => 15,
                    'AktifTigaHari' => 10,
                    'EmailBounce' => -10,
                    'TidakAktifEmpatBelasHari' => -20,
                ],
                'keterangan' => 'Bobot skor per peristiwa prospek.',
            ],
            self::SKOR_AMBANG_QUALIFIED => [
                'bawaan' => 40,
                'keterangan' => 'Skor minimal sebelum prospek dianggap qualified.',
            ],
            self::ATTRIBUTION_JENDELA_HARI => [
                'bawaan' => 90,
                'keterangan' => 'Umur maksimal first touch yang masih diperhitungkan.',
            ],
            self::CONSENT_VERSI_KEBIJAKAN => [
                'bawaan' => '2026-01',
                'keterangan' => 'Versi kebijakan privasi yang dicatat bersama consent.',
            ],
            self::EMAIL_CAP_HARIAN => [
                'bawaan' => 500,
                'keterangan' => 'Batas pengiriman email pemasaran per hari.',
            ],
            self::OTOMASI_CAP_EKSEKUSI => [
                'bawaan' => 1000,
                'keterangan' => 'Batas eksekusi otomasi per jalannya pekerjaan.',
            ],
            self::OTOMASI_CAP_PERCOBAAN => [
                'bawaan' => 3,
                'keterangan' => 'Batas percobaan ulang satu langkah otomasi sebelum masuk DLQ.',
            ],
            self::REFERRAL_HARI_KEDALUWARSA => [
                'bawaan' => 90,
                'keterangan' => 'Umur tautan referral sebelum kliknya tidak lagi dihitung.',
            ],
        ];
    }

    /** @return list<string> */
    public static function kunci(): array
    {
        return array_keys(self::semua());
    }

    public static function dikenal(string $kunci): bool
    {
        return array_key_exists($kunci, self::semua());
    }

    public static function bawaan(string $kunci): mixed
    {
        return self::semua()[$kunci]['bawaan'] ?? null;
    }

    public static function keterangan(string $kunci): string
    {
        return self::semua()[$kunci]['keterangan'] ?? '';
    }
}
