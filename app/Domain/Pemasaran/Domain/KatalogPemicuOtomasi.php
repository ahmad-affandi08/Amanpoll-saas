<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain;

/** Pemicu otomasi beserta peristiwa yang menghasilkannya (MARKETING.md 17). */
final class KatalogPemicuOtomasi
{
    /** Pemicu yang belum ada sumbernya; tersimpan, tetapi jujur dinyatakan belum berlaku. */
    public const BELUM_ADA_SUMBER = 'BelumAdaSumber';

    /**
     * Pemicu => kode EventPemasaran yang menyalakannya.
     *
     * @return array<string, string>
     */
    public static function semua(): array
    {
        return [
            'ProspekDibuat' => KatalogPeristiwaPemasaran::PROSPEK_DIBUAT,
            'FormulirDikirim' => KatalogPeristiwaPemasaran::FORMULIR_DIKIRIM,
            'DemoDimulai' => KatalogPeristiwaPemasaran::DEMO_DIMULAI,
            'DemoSelesai' => KatalogPeristiwaPemasaran::DEMO_SELESAI,
            'HalamanHargaDilihat' => KatalogPeristiwaPemasaran::HARGA_DILIHAT,
            'TrialDimulai' => KatalogPeristiwaPemasaran::TRIAL_DIMULAI,
            'AsetPertamaDibuat' => KatalogPeristiwaPemasaran::ASET_PERTAMA_DIBUAT,
            'PerintahKerjaPertamaDibuat' => KatalogPeristiwaPemasaran::PERINTAH_KERJA_PERTAMA_DIBUAT,
            'TrialTeraktivasi' => KatalogPeristiwaPemasaran::TRIAL_TERAKTIVASI,
            'TrialAkanBerakhir' => KatalogPeristiwaPemasaran::TRIAL_AKAN_BERAKHIR,
            'TrialBerakhir' => KatalogPeristiwaPemasaran::TRIAL_BERAKHIR,
            'LanggananAktif' => KatalogPeristiwaPemasaran::LANGGANAN_DIBUAT,
            'LanggananDibatalkan' => KatalogPeristiwaPemasaran::LANGGANAN_DIBATALKAN,
            'PembayaranGagal' => KatalogPeristiwaPemasaran::PEMBAYARAN_GAGAL,
            'PartnerMengirimLead' => KatalogPeristiwaPemasaran::PARTNER_MENGIRIM_LEAD,
            // Referral lahir di FASE 36, lead tidak aktif menunggu pekerjaan terjadwalnya.
            'ReferralTerdaftar' => self::BELUM_ADA_SUMBER,
            'LeadTidakAktif' => self::BELUM_ADA_SUMBER,
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

    public static function berlaku(string $kode): bool
    {
        return self::peristiwa($kode) !== null;
    }

    /** Kode EventPemasaran yang menyalakan pemicu ini, atau null bila belum ada sumbernya. */
    public static function peristiwa(string $kode): ?string
    {
        $peristiwa = self::semua()[$kode] ?? null;

        return $peristiwa === null || $peristiwa === self::BELUM_ADA_SUMBER ? null : $peristiwa;
    }

    /**
     * Kebalikannya: satu peristiwa dapat menyalakan lebih dari satu pemicu.
     *
     * @return list<string>
     */
    public static function pemicuUntukPeristiwa(string $peristiwa): array
    {
        return array_keys(self::semua(), $peristiwa, true);
    }
}
