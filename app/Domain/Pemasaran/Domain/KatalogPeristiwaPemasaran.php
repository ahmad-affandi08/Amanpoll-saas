<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain;

/** Taxonomy peristiwa pemasaran (MARKETING.md 23). */
final class KatalogPeristiwaPemasaran
{
    // Publik
    public const HALAMAN_DILIHAT = 'HalamanDilihat';

    public const CTA_DIKLIK = 'CTADiklik';

    public const FORMULIR_DIMULAI = 'FormulirDimulai';

    public const FORMULIR_DIKIRIM = 'FormulirDikirim';

    public const DEMO_DIMULAI = 'DemoDimulai';

    public const DEMO_SELESAI = 'DemoSelesai';

    public const HARGA_DILIHAT = 'HargaDilihat';

    public const ARTIKEL_DILIHAT = 'ArtikelDilihat';

    public const TEMPLATE_DIUNDUH = 'TemplateDiunduh';

    public const PROSPEK_DIBUAT = 'ProspekDibuat';

    // Trial
    public const TRIAL_DIMULAI = 'TrialDimulai';

    public const LOKASI_PERTAMA_DIBUAT = 'LokasiPertamaDibuat';

    public const ASET_PERTAMA_DIBUAT = 'AsetPertamaDibuat';

    public const PENGGUNA_PERTAMA_DIUNDANG = 'PenggunaPertamaDiundang';

    public const PERINTAH_KERJA_PERTAMA_DIBUAT = 'PerintahKerjaPertamaDibuat';

    public const PREVENTIVE_PERTAMA_DIBUAT = 'PreventivePertamaDibuat';

    public const TRIAL_TERAKTIVASI = 'TrialTeraktivasi';

    public const TRIAL_AKAN_BERAKHIR = 'TrialAkanBerakhir';

    public const TRIAL_BERAKHIR = 'TrialBerakhir';

    // Revenue
    public const CHECKOUT_DIMULAI = 'CheckoutDimulai';

    public const LANGGANAN_DIBUAT = 'LanggananDibuat';

    public const PEMBAYARAN_BERHASIL = 'PembayaranBerhasil';

    public const PEMBAYARAN_GAGAL = 'PembayaranGagal';

    public const UPGRADE_DILAKUKAN = 'UpgradeDilakukan';

    public const DOWNGRADE_DILAKUKAN = 'DowngradeDilakukan';

    public const LANGGANAN_DIBATALKAN = 'LanggananDibatalkan';

    // Referral dan partner
    public const REFERRAL_DIKLIK = 'ReferralDiklik';

    public const REFERRAL_MENJADI_LEAD = 'ReferralMenjadiLead';

    public const REFERRAL_MENJADI_TRIAL = 'ReferralMenjadiTrial';

    public const REFERRAL_MENJADI_PAID = 'ReferralMenjadiPaid';

    public const PARTNER_MENGIRIM_LEAD = 'PartnerMengirimLead';

    public const KOMISI_PARTNER_DIBUAT = 'KomisiPartnerDibuat';

    /** @return list<string> */
    public static function semua(): array
    {
        /** @var array<string, string> $konstanta */
        $konstanta = (new \ReflectionClass(self::class))->getConstants();

        return array_values($konstanta);
    }

    public static function dikenal(string $jenis): bool
    {
        return in_array($jenis, self::semua(), true);
    }
}
