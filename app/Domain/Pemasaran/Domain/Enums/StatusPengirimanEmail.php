<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Enums;

/** Status satu pengiriman email pemasaran (MARKETING.md 15). */
enum StatusPengirimanEmail: string
{
    case Terjadwal = 'Terjadwal';
    case Dikirim = 'Dikirim';
    case Terkirim = 'Terkirim';
    case Dibuka = 'Dibuka';
    case Diklik = 'Diklik';
    case Bounce = 'Bounce';
    case Gagal = 'Gagal';
    case Unsubscribe = 'Unsubscribe';

    /** Urutan kemajuan; status yang datang terlambat tidak boleh memundurkan yang sudah lebih jauh. */
    public function peringkat(): int
    {
        return match ($this) {
            self::Terjadwal => 0,
            self::Dikirim => 1,
            self::Terkirim => 2,
            self::Dibuka => 3,
            self::Diklik => 4,
            self::Bounce, self::Gagal, self::Unsubscribe => 5,
        };
    }

    public function lebihMajuDari(self $lain): bool
    {
        return $this->peringkat() > $lain->peringkat();
    }

    /** Status akhir yang tidak akan berubah lagi. */
    public function final(): bool
    {
        return in_array($this, [self::Bounce, self::Gagal, self::Unsubscribe], true);
    }

    /** Status yang menandai email benar-benar sudah diserahkan ke penyedia. */
    public function sudahDikirim(): bool
    {
        return $this !== self::Terjadwal && $this !== self::Gagal;
    }
}
