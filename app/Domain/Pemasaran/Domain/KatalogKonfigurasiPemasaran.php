<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain;

use App\Domain\Pemasaran\Domain\Enums\ModelAttribution;

/** Setelan domain Pemasaran beserta nilai bawaannya (MARKETING.md 30). */
final class KatalogKonfigurasiPemasaran
{
    public const TRIAL_PAKET_KODE = 'trial.paket_kode';

    public const TRIAL_KARTU_DIPERLUKAN = 'trial.kartu_diperlukan';

    public const TRIAL_PERPANJANGAN_MAKS_HARI = 'trial.perpanjangan_maks_hari';

    public const SKOR_AMBANG_QUALIFIED = 'skor.ambang_qualified';

    public const ATTRIBUTION_JENDELA_HARI = 'attribution.jendela_hari';

    public const ATTRIBUTION_MODEL = 'attribution.model';

    public const ATTRIBUTION_PARUH_HARI = 'attribution.paruh_hari';

    public const CONSENT_VERSI_KEBIJAKAN = 'consent.versi_kebijakan';

    public const EMAIL_CAP_HARIAN = 'email.cap_harian';

    public const EMAIL_SEQUENCE_TRIAL = 'email.sequence_trial';

    public const WHATSAPP_CAP_PER_NOMOR = 'whatsapp.cap_per_nomor';

    public const WHATSAPP_CAP_JENDELA_JAM = 'whatsapp.cap_jendela_jam';

    public const WHATSAPP_KATA_BERHENTI = 'whatsapp.kata_berhenti';

    public const WHATSAPP_SAPAAN_MENU = 'whatsapp.sapaan_menu';

    public const WHATSAPP_BALASAN_TIDAK_DIKENAL = 'whatsapp.balasan_tidak_dikenal';

    public const WHATSAPP_BALASAN_BERHENTI = 'whatsapp.balasan_berhenti';

    public const OTOMASI_CAP_EKSEKUSI = 'otomasi.cap_eksekusi';

    public const OTOMASI_CAP_PERCOBAAN = 'otomasi.cap_percobaan';

    public const REFERRAL_HARI_KEDALUWARSA = 'referral.hari_kedaluwarsa';

    public const ALERT_TRIAL_KONVERSI_MIN = 'alert.trial_konversi_min_persen';

    public const ALERT_LEAD_DIAM_HARI = 'alert.lead_diam_hari';

    public const ALERT_LEAD_DIAM_MIN = 'alert.lead_diam_min_jumlah';

    public const ALERT_BOUNCE_MAKS = 'alert.bounce_maks_persen';

    public const ALERT_KAMPANYE_VISITOR_MIN = 'alert.kampanye_visitor_min';

    public const ALERT_HALAMAN_VIEW_MIN = 'alert.halaman_view_min';

    public const ALERT_WHATSAPP_GAGAL_MAKS = 'alert.whatsapp_gagal_maks_persen';

    public const EKSPERIMEN_MINIMUM_SAMPEL = 'eksperimen.minimum_sampel';

    /**
     * @return array<string, array{bawaan: mixed, keterangan: string}>
     */
    public static function semua(): array
    {
        return [
            self::TRIAL_PAKET_KODE => [
                'bawaan' => '',
                'keterangan' => 'Kode paket yang dipakai trial; kosong berarti paket aktif termurah.',
            ],
            self::TRIAL_KARTU_DIPERLUKAN => [
                'bawaan' => false,
                'keterangan' => 'Apakah pendaftaran trial menuntut kartu sejak awal.',
            ],
            self::TRIAL_PERPANJANGAN_MAKS_HARI => [
                'bawaan' => 14,
                'keterangan' => 'Batas perpanjangan trial yang boleh diberikan otomasi.',
            ],
            self::SKOR_AMBANG_QUALIFIED => [
                'bawaan' => 40,
                'keterangan' => 'Skor minimal sebelum prospek dianggap qualified.',
            ],
            self::ATTRIBUTION_JENDELA_HARI => [
                'bawaan' => 90,
                'keterangan' => 'Umur maksimal first touch yang masih diperhitungkan.',
            ],
            self::ATTRIBUTION_MODEL => [
                'bawaan' => ModelAttribution::Pertama->value,
                'keterangan' => 'Model pembagian jasa antar sentuhan yang dipakai dashboard.',
            ],
            self::ATTRIBUTION_PARUH_HARI => [
                'bawaan' => 7,
                'keterangan' => 'Paruh waktu model peluruhan, dalam hari.',
            ],
            self::CONSENT_VERSI_KEBIJAKAN => [
                'bawaan' => '2026-01',
                'keterangan' => 'Versi kebijakan privasi yang dicatat bersama consent.',
            ],
            self::EMAIL_CAP_HARIAN => [
                'bawaan' => 500,
                'keterangan' => 'Batas pengiriman email pemasaran per hari.',
            ],
            self::WHATSAPP_CAP_PER_NOMOR => [
                'bawaan' => 3,
                'keterangan' => 'Batas pesan WhatsApp pemasaran ke satu nomor dalam satu jendela waktu.',
            ],
            self::WHATSAPP_CAP_JENDELA_JAM => [
                'bawaan' => 24,
                'keterangan' => 'Panjang jendela frequency cap WhatsApp, dalam jam.',
            ],
            self::WHATSAPP_KATA_BERHENTI => [
                'bawaan' => 'STOP, BERHENTI, UNSUBSCRIBE',
                'keterangan' => 'Kata yang dianggap permintaan berhenti, dipisahkan koma.',
            ],
            self::WHATSAPP_SAPAAN_MENU => [
                'bawaan' => 'Halo, saya asisten Amanpoll.',
                'keterangan' => 'Kalimat pembuka sebelum daftar menu WhatsApp.',
            ],
            self::WHATSAPP_BALASAN_TIDAK_DIKENAL => [
                'bawaan' => 'Maaf, pilihan itu belum ada. Balas dengan angka menu di bawah ini.',
                'keterangan' => 'Balasan ketika pesan masuk tidak cocok dengan menu mana pun.',
            ],
            self::WHATSAPP_BALASAN_BERHENTI => [
                'bawaan' => 'Baik, nomor ini tidak akan menerima pesan pemasaran lagi.',
                'keterangan' => 'Balasan setelah permintaan berhenti diterima.',
            ],
            self::ALERT_WHATSAPP_GAGAL_MAKS => [
                'bawaan' => 10,
                'keterangan' => 'Batas persentase kegagalan WhatsApp sebelum alert dibunyikan.',
            ],
            self::EKSPERIMEN_MINIMUM_SAMPEL => [
                'bawaan' => 200,
                'keterangan' => 'Sampel minimum tiap varian sebelum pemenang boleh dinyatakan.',
            ],
            self::EMAIL_SEQUENCE_TRIAL => [
                'bawaan' => '',
                'keterangan' => 'Kode sequence email yang dijalankan saat trial dimulai; kosong berarti tidak ada.',
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
            self::ALERT_TRIAL_KONVERSI_MIN => [
                'bawaan' => 20,
                'keterangan' => 'Konversi trial ke bayar di bawah persen ini memicu alert.',
            ],
            self::ALERT_LEAD_DIAM_HARI => [
                'bawaan' => 14,
                'keterangan' => 'Berapa hari prospek boleh diam sebelum dihitung tanpa aktivitas.',
            ],
            self::ALERT_LEAD_DIAM_MIN => [
                'bawaan' => 10,
                'keterangan' => 'Jumlah prospek diam yang memicu alert.',
            ],
            self::ALERT_BOUNCE_MAKS => [
                'bawaan' => 5,
                'keterangan' => 'Rasio bounce email di atas persen ini memicu alert.',
            ],
            self::ALERT_KAMPANYE_VISITOR_MIN => [
                'bawaan' => 100,
                'keterangan' => 'Kampanye dengan visitor sebanyak ini tetapi nol trial memicu alert.',
            ],
            self::ALERT_HALAMAN_VIEW_MIN => [
                'bawaan' => 200,
                'keterangan' => 'Kunjungan halaman sebanyak ini tanpa satu pun formulir memicu alert.',
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
