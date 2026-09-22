<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain;

/** Izin konsol Growth & Marketing (MARKETING.md 26). */
final class KatalogIzinPemasaran
{
    public const PEMASARAN_LIHAT = 'platform.pemasaran.lihat';

    public const PEMASARAN_KELOLA = 'platform.pemasaran.kelola';

    public const PROSPEK_LIHAT = 'platform.prospek.lihat';

    public const PROSPEK_KELOLA = 'platform.prospek.kelola';

    /** Ekspor dipisah dari lihat: memindahkan data keluar sistem bukan membaca. */
    public const PROSPEK_EKSPOR = 'platform.prospek.ekspor';

    public const KAMPANYE_LIHAT = 'platform.kampanye.lihat';

    public const KAMPANYE_KELOLA = 'platform.kampanye.kelola';

    public const HALAMAN_LIHAT = 'platform.halaman.lihat';

    public const HALAMAN_KELOLA = 'platform.halaman.kelola';

    /** Menerbitkan berarti mengubah isi situs publik, jadi haknya sendiri. */
    public const HALAMAN_TERBITKAN = 'platform.halaman.terbitkan';

    public const KONTEN_LIHAT = 'platform.konten.lihat';

    public const KONTEN_KELOLA = 'platform.konten.kelola';

    public const KONTEN_TERBITKAN = 'platform.konten.terbitkan';

    public const OTOMASI_LIHAT = 'platform.otomasi.lihat';

    public const OTOMASI_KELOLA = 'platform.otomasi.kelola';

    /** Mengaktifkan otomasi mulai mengirim pesan ke orang sungguhan. */
    public const OTOMASI_AKTIFKAN = 'platform.otomasi.aktifkan';

    public const EMAIL_LIHAT = 'platform.email.lihat';

    public const EMAIL_KELOLA = 'platform.email.kelola';

    public const WHATSAPP_LIHAT = 'platform.whatsapp.lihat';

    public const WHATSAPP_KELOLA = 'platform.whatsapp.kelola';

    public const REFERRAL_LIHAT = 'platform.referral.lihat';

    public const REFERRAL_KELOLA = 'platform.referral.kelola';

    public const PARTNER_LIHAT = 'platform.partner.lihat';

    public const PARTNER_KELOLA = 'platform.partner.kelola';

    public const ANALYTICS_LIHAT = 'platform.analytics.lihat';

    public const EKSPERIMEN_KELOLA = 'platform.eksperimen.kelola';

    /** @return list<string> */
    public static function semua(): array
    {
        /** @var array<string, string> $konstanta */
        $konstanta = (new \ReflectionClass(self::class))->getConstants();

        return array_values($konstanta);
    }

    public static function dikenal(string $kode): bool
    {
        return in_array($kode, self::semua(), true);
    }
}
