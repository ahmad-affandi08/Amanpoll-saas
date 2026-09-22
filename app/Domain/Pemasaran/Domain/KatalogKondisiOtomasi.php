<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain;

use App\Domain\Pemasaran\Domain\Enums\OperatorKondisi;

/** Bidang yang boleh diperiksa satu kondisi otomasi (MARKETING.md 17). */
final class KatalogKondisiOtomasi
{
    public const INDUSTRI = 'Industri';

    public const SUMBER = 'Sumber';

    public const KAMPANYE = 'Kampanye';

    public const SKOR = 'Skor';

    public const PAKET = 'Paket';

    public const AKTIVITAS_TERAKHIR_HARI = 'AktivitasTerakhirHari';

    public const STATUS_TRIAL = 'StatusTrial';

    public const JUMLAH_ASET = 'JumlahAset';

    public const JUMLAH_LOKASI = 'JumlahLokasi';

    public const TAG = 'Tag';

    public const CONSENT = 'Consent';

    public const STATUS_LANGGANAN = 'StatusLangganan';

    public const TAHAP_PIPELINE = 'TahapPipeline';

    /**
     * Bidang => apakah nilainya angka.
     *
     * @return array<string, bool>
     */
    public static function semua(): array
    {
        return [
            self::INDUSTRI => false,
            self::SUMBER => false,
            self::KAMPANYE => false,
            self::SKOR => true,
            self::PAKET => false,
            self::AKTIVITAS_TERAKHIR_HARI => true,
            self::STATUS_TRIAL => false,
            self::JUMLAH_ASET => true,
            self::JUMLAH_LOKASI => true,
            self::TAG => false,
            self::CONSENT => false,
            self::STATUS_LANGGANAN => false,
            self::TAHAP_PIPELINE => false,
        ];
    }

    /** @return list<string> */
    public static function kode(): array
    {
        return array_keys(self::semua());
    }

    public static function dikenal(string $bidang): bool
    {
        return array_key_exists($bidang, self::semua());
    }

    public static function numerik(string $bidang): bool
    {
        return self::semua()[$bidang] ?? false;
    }

    /**
     * Operator yang masuk akal untuk satu bidang; bidang teks tidak dibandingkan besar-kecil.
     *
     * @return list<string>
     */
    public static function operator(string $bidang): array
    {
        $umum = [
            OperatorKondisi::SamaDengan,
            OperatorKondisi::TidakSamaDengan,
            OperatorKondisi::SalahSatuDari,
            OperatorKondisi::Ada,
            OperatorKondisi::TidakAda,
        ];

        $khusus = self::numerik($bidang)
            ? [OperatorKondisi::LebihDari, OperatorKondisi::KurangDari]
            : [OperatorKondisi::Mengandung];

        return array_map(
            fn (OperatorKondisi $satu): string => $satu->value,
            [...$umum, ...$khusus],
        );
    }
}
