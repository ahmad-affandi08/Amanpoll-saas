<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain;

/**
 * Feature flag modul pemasaran (MARKETING.md 31).
 *
 * Kode tersimpan di tabel FiturPlatform dan dirujuk rute, jadi jangan diubah
 * namanya — tambah kode baru dan pensiunkan yang lama.
 */
final class KatalogFiturPlatform
{
    public const CRM = 'marketing.crm';

    public const CMS = 'marketing.cms';

    public const OTOMASI = 'marketing.automation';

    public const EMAIL = 'marketing.email';

    public const WHATSAPP = 'marketing.whatsapp';

    public const SOSIAL = 'marketing.social';

    public const REFERRAL = 'marketing.referral';

    public const PARTNER = 'marketing.partner';

    public const EKSPERIMEN = 'marketing.experiment';

    public const ANALITIK = 'marketing.analytics';

    /**
     * Nama yang tampil di konsol, beserta keterangan singkatnya.
     *
     * @return array<string, array{nama: string, keterangan: string}>
     */
    public static function semua(): array
    {
        return [
            self::CRM => ['nama' => 'CRM Prospek', 'keterangan' => 'Daftar prospek, pipeline, skor, dan timeline.'],
            self::CMS => ['nama' => 'Konten & SEO', 'keterangan' => 'Artikel, keyword, dan metadata situs publik.'],
            self::OTOMASI => ['nama' => 'Otomasi Pemasaran', 'keterangan' => 'Alur trigger, kondisi, jeda, dan aksi.'],
            self::EMAIL => ['nama' => 'Email Pemasaran', 'keterangan' => 'Templat, sequence, dan pengiriman email.'],
            self::WHATSAPP => ['nama' => 'WhatsApp', 'keterangan' => 'Templat dan pengiriman pesan WhatsApp.'],
            self::SOSIAL => ['nama' => 'Social Scheduler', 'keterangan' => 'Penjadwalan konten media sosial.'],
            self::REFERRAL => ['nama' => 'Referral', 'keterangan' => 'Program referral dan rewardnya.'],
            self::PARTNER => ['nama' => 'Partner', 'keterangan' => 'Program partner, lead, dan komisi.'],
            self::EKSPERIMEN => ['nama' => 'Eksperimen A/B', 'keterangan' => 'Varian halaman dan pengukurannya.'],
            self::ANALITIK => ['nama' => 'Analytics', 'keterangan' => 'Dasbor pertumbuhan dan attribution.'],
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
}
