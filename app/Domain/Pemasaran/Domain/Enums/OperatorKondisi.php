<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Enums;

/** Operator perbandingan satu kondisi otomasi (MARKETING.md 17). */
enum OperatorKondisi: string
{
    case SamaDengan = 'SamaDengan';
    case TidakSamaDengan = 'TidakSamaDengan';
    case LebihDari = 'LebihDari';
    case KurangDari = 'KurangDari';
    case SalahSatuDari = 'SalahSatuDari';
    case Mengandung = 'Mengandung';
    case Ada = 'Ada';
    case TidakAda = 'TidakAda';

    /** Operator yang tidak membutuhkan nilai pembanding. */
    public function tanpaNilai(): bool
    {
        return $this === self::Ada || $this === self::TidakAda;
    }

    /** Operator yang membandingkan angka, bukan teks. */
    public function numerik(): bool
    {
        return $this === self::LebihDari || $this === self::KurangDari;
    }

    /** Operator yang menerima daftar nilai. */
    public function berdaftar(): bool
    {
        return $this === self::SalahSatuDari;
    }
}
