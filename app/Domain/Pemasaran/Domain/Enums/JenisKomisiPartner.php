<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Enums;

/** Bentuk perhitungan komisi; keduanya bertumpu pada jumlah pembayaran yang benar-benar masuk (MARKETING.md 21). */
enum JenisKomisiPartner: string
{
    case Persentase = 'Persentase';
    case Tetap = 'Tetap';

    public function hitung(float $nilaiAturan, float $jumlahPembayaran): float
    {
        $komisi = match ($this) {
            self::Persentase => $jumlahPembayaran * $nilaiAturan / 100,
            // Nominal tetap tidak pernah melampaui pembayaran yang melahirkannya.
            self::Tetap => min($nilaiAturan, $jumlahPembayaran),
        };

        return round(max(0.0, $komisi), 2);
    }

    public function label(): string
    {
        return match ($this) {
            self::Persentase => 'Persentase dari pembayaran',
            self::Tetap => 'Nominal tetap per pembayaran',
        };
    }

    /** @return list<string> */
    public static function nilai(): array
    {
        return array_column(self::cases(), 'value');
    }
}
