<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain;

use App\Domain\Pelaporan\Domain\Enums\SatuanKpi;
use App\Domain\Pemasaran\Domain\Enums\KelompokKpiPemasaran;
use App\Domain\Pemasaran\Domain\ValueObjects\DefinisiKpiPemasaran;
use App\Shared\Domain\Exceptions\DataTidakDitemukan;

/** Katalog KPI dashboard growth beserta rumus dan sumbernya (MARKETING.md 5, Gate 37). */
final class KatalogKpiPemasaran
{
    public const VISITOR = 'visitor';

    public const VISITOR_UNIK = 'visitor_unik';

    public const HALAMAN_DILIHAT = 'halaman_dilihat';

    public const FORMULIR_DIKIRIM = 'formulir_dikirim';

    public const DEMO_DIMULAI = 'demo_dimulai';

    public const DEMO_SELESAI = 'demo_selesai';

    public const TRIAL_TERDAFTAR = 'trial_terdaftar';

    public const TRIAL_TERAKTIVASI = 'trial_teraktivasi';

    public const LEAD_QUALIFIED = 'lead_qualified';

    public const PELANGGAN_BAYAR = 'pelanggan_bayar';

    public const MRR_BARU = 'mrr_baru';

    public const VISITOR_KE_LEAD = 'visitor_ke_lead';

    public const VISITOR_KE_TRIAL = 'visitor_ke_trial';

    public const TRIAL_KE_AKTIVASI = 'trial_ke_aktivasi';

    public const AKTIVASI_KE_BAYAR = 'aktivasi_ke_bayar';

    public const REVENUE_PER_CHANNEL = 'revenue_per_channel';

    public const REFERRAL_KONVERSI = 'referral_konversi';

    public const CAC_PER_CHANNEL = 'cac_per_channel';

    public const REVENUE_PARTNER = 'revenue_partner';

    /** @var array<string, DefinisiKpiPemasaran>|null */
    private static ?array $cache = null;

    /** @return array<string, DefinisiKpiPemasaran> */
    public static function semua(): array
    {
        return self::$cache ??= self::bangun();
    }

    public static function ada(string $kunci): bool
    {
        return isset(self::semua()[$kunci]);
    }

    public static function ambil(string $kunci): DefinisiKpiPemasaran
    {
        return self::semua()[$kunci]
            ?? throw new DataTidakDitemukan("KPI pemasaran {$kunci} tidak dikenal.");
    }

    /** @return list<string> */
    public static function kunci(): array
    {
        return array_keys(self::semua());
    }

    /** @return list<string> */
    public static function kunciTersedia(): array
    {
        return array_keys(array_filter(
            self::semua(),
            fn (DefinisiKpiPemasaran $satu): bool => $satu->tersedia(),
        ));
    }

    /** @return array<string, DefinisiKpiPemasaran> */
    private static function bangun(): array
    {
        $daftar = [
            // Trafik ------------------------------------------------------
            new DefinisiKpiPemasaran(
                self::VISITOR, 'Visitor', KelompokKpiPemasaran::Trafik, SatuanKpi::Jumlah,
                'COUNT(SesiPengunjung) yang DimulaiPada-nya di dalam rentang.',
                'SesiPengunjung',
            ),
            new DefinisiKpiPemasaran(
                self::VISITOR_UNIK, 'Visitor Unik', KelompokKpiPemasaran::Trafik, SatuanKpi::Jumlah,
                'COUNT(DISTINCT SesiPengunjung.PengenalPengunjung) di dalam rentang.',
                'SesiPengunjung',
            ),
            new DefinisiKpiPemasaran(
                self::HALAMAN_DILIHAT, 'Landing Page View', KelompokKpiPemasaran::Trafik, SatuanKpi::Jumlah,
                'COUNT(EventPemasaran) berjenis HalamanDilihat di dalam rentang.',
                'EventPemasaran',
            ),

            // Konversi ----------------------------------------------------
            new DefinisiKpiPemasaran(
                self::FORMULIR_DIKIRIM, 'Form Submit', KelompokKpiPemasaran::Konversi, SatuanKpi::Jumlah,
                'COUNT(PengirimanFormulir) yang DikirimPada-nya di dalam rentang.',
                'PengirimanFormulir',
            ),
            new DefinisiKpiPemasaran(
                self::DEMO_DIMULAI, 'Demo Started', KelompokKpiPemasaran::Konversi, SatuanKpi::Jumlah,
                'COUNT(DISTINCT PengenalPengunjung) pada EventPemasaran berjenis DemoDimulai.',
                'EventPemasaran',
            ),
            new DefinisiKpiPemasaran(
                self::DEMO_SELESAI, 'Demo Completed', KelompokKpiPemasaran::Konversi, SatuanKpi::Jumlah,
                'COUNT(DISTINCT PengenalPengunjung) pada EventPemasaran berjenis DemoSelesai.',
                'EventPemasaran',
            ),
            new DefinisiKpiPemasaran(
                self::LEAD_QUALIFIED, 'Qualified Lead', KelompokKpiPemasaran::Konversi, SatuanKpi::Jumlah,
                'COUNT(Prospek) yang Skor-nya mencapai ambang skor.ambang_qualified.',
                'Prospek',
            ),

            // Trial -------------------------------------------------------
            new DefinisiKpiPemasaran(
                self::TRIAL_TERDAFTAR, 'Trial Registered', KelompokKpiPemasaran::Trial, SatuanKpi::Jumlah,
                'COUNT(Trial) yang MulaiPada-nya di dalam rentang.',
                'Trial',
            ),
            new DefinisiKpiPemasaran(
                self::TRIAL_TERAKTIVASI, 'Trial Activated', KelompokKpiPemasaran::Trial, SatuanKpi::Jumlah,
                'COUNT(Trial) yang TeraktivasiPada-nya terisi di dalam rentang.',
                'Trial',
            ),

            // Revenue -----------------------------------------------------
            new DefinisiKpiPemasaran(
                self::PELANGGAN_BAYAR, 'Paid Customer', KelompokKpiPemasaran::Revenue, SatuanKpi::Jumlah,
                'COUNT(Trial) yang KonversiPada-nya terisi di dalam rentang.',
                'Trial',
            ),
            new DefinisiKpiPemasaran(
                self::MRR_BARU, 'MRR Baru', KelompokKpiPemasaran::Revenue, SatuanKpi::Uang,
                'SUM(PembayaranLangganan.Jumlah) berstatus Berhasil dari organisasi yang trialnya '
                .'berkonversi di dalam rentang.',
                'PembayaranLangganan',
            ),

            // Rasio -------------------------------------------------------
            new DefinisiKpiPemasaran(
                self::VISITOR_KE_LEAD, 'Visitor → Lead', KelompokKpiPemasaran::Konversi, SatuanKpi::Persen,
                'Lead / Visitor unik x 100, keduanya dihitung dari funnel yang sama.',
                'SesiPengunjung, Prospek',
            ),
            new DefinisiKpiPemasaran(
                self::VISITOR_KE_TRIAL, 'Visitor → Trial', KelompokKpiPemasaran::Konversi, SatuanKpi::Persen,
                'Trial / Visitor unik x 100, keduanya dihitung dari funnel yang sama.',
                'SesiPengunjung, Trial',
            ),
            new DefinisiKpiPemasaran(
                self::TRIAL_KE_AKTIVASI, 'Trial → Activated', KelompokKpiPemasaran::Trial, SatuanKpi::Persen,
                'Activated / Trial x 100, keduanya dihitung dari funnel yang sama.',
                'Trial',
            ),
            new DefinisiKpiPemasaran(
                self::AKTIVASI_KE_BAYAR, 'Activated → Paid', KelompokKpiPemasaran::Revenue, SatuanKpi::Persen,
                'Paid / Activated x 100, keduanya dihitung dari funnel yang sama.',
                'Trial',
            ),

            // Channel dan referral ----------------------------------------
            new DefinisiKpiPemasaran(
                self::REVENUE_PER_CHANNEL, 'Revenue per Channel', KelompokKpiPemasaran::Channel, SatuanKpi::Uang,
                'SUM(PembayaranLangganan.Jumlah) berstatus Berhasil, dikelompokkan menurut '
                .'AttributionPemasaran.SumberPertama pengunjung yang menjadi organisasinya.',
                'PembayaranLangganan, AttributionPemasaran',
            ),
            new DefinisiKpiPemasaran(
                self::REFERRAL_KONVERSI, 'Referral Conversion', KelompokKpiPemasaran::Referral, SatuanKpi::Persen,
                'COUNT(Referral berstatus Paid ke atas) / COUNT(Referral yang pernah diklik) x 100.',
                'Referral',
            ),

            new DefinisiKpiPemasaran(
                self::CAC_PER_CHANNEL, 'CAC per Channel', KelompokKpiPemasaran::Channel, SatuanKpi::Uang,
                'SUM(KampanyeBiaya.Jumlah) / jumlah pelanggan baru seluruh kampanye. Kartu ini menampilkan '
                .'CAC gabungan; pecahannya per channel hanya pasti untuk kampanye berchannel tunggal dan '
                .'dirinci di panel CAC.',
                'KampanyeBiaya, Trial, AttributionPemasaran',
                naikItuBaik: false,
            ),

            new DefinisiKpiPemasaran(
                self::REVENUE_PARTNER, 'Partner-sourced Revenue', KelompokKpiPemasaran::Channel, SatuanKpi::Uang,
                'SUM(PembayaranLangganan.Jumlah) dari organisasi yang punya LeadPartner tidak ditolak.',
                'LeadPartner, PembayaranLangganan',
            ),
        ];

        $peta = [];

        foreach ($daftar as $definisi) {
            $peta[$definisi->kunci] = $definisi;
        }

        return $peta;
    }
}
