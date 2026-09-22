<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain;

use App\Domain\Pemasaran\Domain\Enums\TingkatAlertPemasaran;

/** Alert growth beserta ambang dan sumbernya (MARKETING.md 5). */
final class KatalogAlertPemasaran
{
    public const TRIAL_KONVERSI_TURUN = 'trial_konversi_turun';

    public const LEAD_TANPA_AKTIVITAS = 'lead_tanpa_aktivitas';

    public const OTOMASI_GAGAL = 'otomasi_gagal';

    public const EMAIL_BOUNCE_NAIK = 'email_bounce_naik';

    public const KAMPANYE_TANPA_TRIAL = 'kampanye_tanpa_trial';

    public const REWARD_REFERRAL_GAGAL = 'reward_referral_gagal';

    public const KONVERSI_HALAMAN_ANOMALI = 'konversi_halaman_anomali';

    public const WHATSAPP_GAGAL_KIRIM = 'whatsapp_gagal_kirim';

    public const KOMISI_PARTNER_TERTUNDA = 'komisi_partner_tertunda';

    /**
     * Kode alert => tingkat, judul, sumber, dan alasan bila belum dapat diperiksa.
     *
     * @return array<string, array{tingkat: TingkatAlertPemasaran, judul: string, sumber: string, belumTersedia: string|null}>
     */
    public static function semua(): array
    {
        return [
            self::TRIAL_KONVERSI_TURUN => [
                'tingkat' => TingkatAlertPemasaran::Kritis,
                'judul' => 'Konversi trial turun',
                'sumber' => 'Trial',
                'belumTersedia' => null,
            ],
            self::LEAD_TANPA_AKTIVITAS => [
                'tingkat' => TingkatAlertPemasaran::Peringatan,
                'judul' => 'Banyak lead tanpa aktivitas',
                'sumber' => 'Prospek',
                'belumTersedia' => null,
            ],
            self::OTOMASI_GAGAL => [
                'tingkat' => TingkatAlertPemasaran::Kritis,
                'judul' => 'Eksekusi otomasi berhenti di DLQ',
                'sumber' => 'EksekusiOtomasiPemasaran',
                'belumTersedia' => null,
            ],
            self::EMAIL_BOUNCE_NAIK => [
                'tingkat' => TingkatAlertPemasaran::Kritis,
                'judul' => 'Bounce email meningkat',
                'sumber' => 'PengirimanEmailPemasaran',
                'belumTersedia' => null,
            ],
            self::KAMPANYE_TANPA_TRIAL => [
                'tingkat' => TingkatAlertPemasaran::Peringatan,
                'judul' => 'Kampanye berjalan tanpa menghasilkan trial',
                'sumber' => 'MetrikKampanye',
                'belumTersedia' => null,
            ],
            self::REWARD_REFERRAL_GAGAL => [
                'tingkat' => TingkatAlertPemasaran::Peringatan,
                'judul' => 'Imbalan referral gagal diberikan',
                'sumber' => 'RewardReferral',
                'belumTersedia' => null,
            ],
            self::KONVERSI_HALAMAN_ANOMALI => [
                'tingkat' => TingkatAlertPemasaran::Peringatan,
                'judul' => 'Landing page ramai tetapi tidak menghasilkan lead',
                'sumber' => 'EventPemasaran, PengirimanFormulir',
                'belumTersedia' => null,
            ],
            self::WHATSAPP_GAGAL_KIRIM => [
                'tingkat' => TingkatAlertPemasaran::Kritis,
                'judul' => 'Pengiriman WhatsApp gagal meningkat',
                'sumber' => 'PengirimanWhatsApp',
                'belumTersedia' => 'Kanal WhatsApp lahir di FASE 38.01; belum ada kiriman yang dapat gagal.',
            ],
            self::KOMISI_PARTNER_TERTUNDA => [
                'tingkat' => TingkatAlertPemasaran::Peringatan,
                'judul' => 'Komisi partner tertunda',
                'sumber' => 'KomisiPartner',
                'belumTersedia' => 'Program partner lahir di FASE 38.09; belum ada komisi yang dapat tertunda.',
            ],
        ];
    }

    /** @return list<string> */
    public static function kode(): array
    {
        return array_keys(self::semua());
    }

    public static function dikenal(string $kode): bool
    {
        return array_key_exists($kode, self::semua());
    }

    public static function tersedia(string $kode): bool
    {
        return array_key_exists($kode, self::semua()) && self::semua()[$kode]['belumTersedia'] === null;
    }

    public static function tingkat(string $kode): TingkatAlertPemasaran
    {
        return self::semua()[$kode]['tingkat'] ?? TingkatAlertPemasaran::Info;
    }

    public static function judul(string $kode): string
    {
        return self::semua()[$kode]['judul'] ?? $kode;
    }
}
