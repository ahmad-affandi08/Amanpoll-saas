<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Enums;

/** Tindakan yang ditutup di dalam demo agar sandbox tidak dipakai sebagai produk gratis (MARKETING.md 11). */
enum FiturDibatasiDemo: string
{
    case Ekspor = 'Ekspor';
    case Undangan = 'Undangan';
    case Integrasi = 'Integrasi';
    case Pembayaran = 'Pembayaran';
    case HapusData = 'HapusData';
    case UbahPengaturan = 'UbahPengaturan';
}
