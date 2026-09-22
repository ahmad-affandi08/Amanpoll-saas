<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Enums;

/** Status satu pengiriman WhatsApp (MARKETING.md 16). */
enum StatusPengirimanWhatsApp: string
{
    case Terjadwal = 'Terjadwal';
    case Dikirim = 'Dikirim';
    case Terkirim = 'Terkirim';
    case Dibaca = 'Dibaca';
    case Gagal = 'Gagal';
    case Ditolak = 'Ditolak';
    case Unsubscribe = 'Unsubscribe';

    /** Urutan kemajuan; kabar yang datang terlambat tidak memundurkan yang sudah lebih jauh. */
    public function peringkat(): int
    {
        return match ($this) {
            self::Terjadwal => 0,
            self::Dikirim => 1,
            self::Terkirim => 2,
            self::Dibaca => 3,
            self::Gagal, self::Ditolak, self::Unsubscribe => 4,
        };
    }

    public function lebihMajuDari(self $lain): bool
    {
        return $this->peringkat() > $lain->peringkat();
    }

    public function final(): bool
    {
        return in_array($this, [self::Gagal, self::Ditolak, self::Unsubscribe], true);
    }

    /** Kiriman yang benar-benar sudah diserahkan ke penyedia; inilah yang dihitung frequency cap. */
    public function sudahDikirim(): bool
    {
        return in_array($this, [self::Dikirim, self::Terkirim, self::Dibaca], true);
    }
}
