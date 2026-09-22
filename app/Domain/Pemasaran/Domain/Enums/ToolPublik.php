<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Enums;

/**
 * Daftar tertutup tools publik beserta jalurnya. Jalur-jalur ini menempati rak
 * `/tools` yang sama dengan konten berjenis FreeTool, jadi CMS harus tahu mana
 * yang sudah terpakai (MARKETING.md 10).
 */
enum ToolPublik: string
{
    case KalkulatorMttr = 'kalkulator-mttr';
    case KalkulatorMtbf = 'kalkulator-mtbf';
    case KalkulatorDowntime = 'kalkulator-downtime';
    case QrAset = 'qr-aset';

    public function jalur(): string
    {
        return '/tools/'.$this->value;
    }

    public function judul(): string
    {
        return match ($this) {
            self::KalkulatorMttr => 'Kalkulator MTTR',
            self::KalkulatorMtbf => 'Kalkulator MTBF',
            self::KalkulatorDowntime => 'Kalkulator Downtime',
            self::QrAset => 'Generator QR Aset',
        };
    }

    /** Ketiga kalkulator memakai rumus yang sama; yang berbeda hanya angka yang disorot. */
    public function metrikSorotan(): ?string
    {
        return match ($this) {
            self::KalkulatorMttr => 'Mttr',
            self::KalkulatorMtbf => 'Mtbf',
            self::KalkulatorDowntime => 'TotalJam',
            self::QrAset => null,
        };
    }

    /** @return list<string> */
    public static function seluruhJalur(): array
    {
        return array_map(fn (self $satu): string => $satu->jalur(), self::cases());
    }

    public static function menempati(string $jalur): bool
    {
        return in_array($jalur, self::seluruhJalur(), true);
    }
}
