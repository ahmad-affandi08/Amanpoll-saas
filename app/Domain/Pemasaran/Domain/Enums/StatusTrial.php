<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Enums;

/** Siklus hidup satu trial (MARKETING.md 12). */
enum StatusTrial: string
{
    case Terdaftar = 'Terdaftar';
    case Setup = 'Setup';
    case Aktif = 'Aktif';
    case Teraktivasi = 'Teraktivasi';
    case Konversi = 'Konversi';
    case Kadaluarsa = 'Kadaluarsa';
    case Dibatalkan = 'Dibatalkan';
    case Diperpanjang = 'Diperpanjang';

    /** @return list<self> */
    public function tujuanYangDiizinkan(): array
    {
        return match ($this) {
            // Konversi dapat datang dari keadaan mana pun yang masih hidup: orang
            // yang langsung membayar tanpa menyentuh checklist tetap pelanggan.
            self::Terdaftar => [self::Setup, self::Konversi, self::Kadaluarsa, self::Dibatalkan],
            self::Setup => [self::Aktif, self::Konversi, self::Diperpanjang, self::Kadaluarsa, self::Dibatalkan],
            self::Aktif => [self::Teraktivasi, self::Konversi, self::Diperpanjang, self::Kadaluarsa, self::Dibatalkan],
            self::Teraktivasi => [self::Konversi, self::Diperpanjang, self::Kadaluarsa, self::Dibatalkan],
            self::Diperpanjang => [self::Aktif, self::Teraktivasi, self::Konversi, self::Kadaluarsa, self::Dibatalkan],
            // Konversi adalah akhir yang membahagiakan; sesudahnya urusan Langganan.
            self::Konversi, self::Dibatalkan => [],
            self::Kadaluarsa => [self::Diperpanjang, self::Konversi],
        };
    }

    public function bolehPindahKe(self $tujuan): bool
    {
        return in_array($tujuan, $this->tujuanYangDiizinkan(), true);
    }

    /** Trial yang masih dapat menerima butir aktivasi baru. */
    public function berjalan(): bool
    {
        return in_array($this, [self::Terdaftar, self::Setup, self::Aktif, self::Diperpanjang], true);
    }

    public function selesai(): bool
    {
        return in_array($this, [self::Konversi, self::Dibatalkan, self::Kadaluarsa], true);
    }
}
