<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Enums;

/** Perjalanan satu referral (MARKETING.md 20). */
enum StatusReferral: string
{
    case Dibuat = 'Dibuat';
    case Diklik = 'Diklik';
    case Lead = 'Lead';
    case Trial = 'Trial';
    case Paid = 'Paid';
    case RewardPending = 'RewardPending';
    case Rewarded = 'Rewarded';
    case Kedaluwarsa = 'Kedaluwarsa';
    case Ditolak = 'Ditolak';

    /** Urutan maju; kabar yang datang terlambat tidak memundurkan yang sudah lebih jauh. */
    public function peringkat(): int
    {
        return match ($this) {
            self::Dibuat => 0,
            self::Diklik => 1,
            self::Lead => 2,
            self::Trial => 3,
            self::Paid => 4,
            self::RewardPending => 5,
            self::Rewarded => 6,
            self::Kedaluwarsa, self::Ditolak => 7,
        };
    }

    public function lebihMajuDari(self $lain): bool
    {
        return $this->peringkat() > $lain->peringkat();
    }

    /** Status akhir yang tidak akan berubah lagi. */
    public function final(): bool
    {
        return in_array($this, [self::Rewarded, self::Kedaluwarsa, self::Ditolak], true);
    }

    /** Referral yang sudah menghasilkan pembayaran; sejak titik ini kedaluwarsa tidak lagi berlaku. */
    public function sudahBerbuah(): bool
    {
        return $this->peringkat() >= self::Paid->peringkat() && $this !== self::Kedaluwarsa
            && $this !== self::Ditolak;
    }
}
