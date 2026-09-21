<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Domain\Enums;

/**
 * Pengelompokan KPI sesuai sumber transaksinya (TASK 21.01). Satu kelompok
 * dilayani tepat satu PenyediaKpi.
 */
enum KelompokKpi: string
{
    case Aset = 'Aset';
    case Keluhan = 'Keluhan';
    case PerintahKerja = 'PerintahKerja';
    case TingkatLayanan = 'TingkatLayanan';
    case Keandalan = 'Keandalan';
    case Biaya = 'Biaya';
    case Stok = 'Stok';
    case Kalibrasi = 'Kalibrasi';
    case Preventif = 'Preventif';
    case Pengadaan = 'Pengadaan';
    case Anggaran = 'Anggaran';
    case Kontrak = 'Kontrak';
    case Kepatuhan = 'Kepatuhan';

    public function label(): string
    {
        return match ($this) {
            self::Aset => 'Aset',
            self::Keluhan => 'Keluhan',
            self::PerintahKerja => 'Perintah Kerja',
            self::TingkatLayanan => 'Tingkat Layanan',
            self::Keandalan => 'Keandalan',
            self::Biaya => 'Biaya',
            self::Stok => 'Persediaan',
            self::Kalibrasi => 'Kalibrasi',
            self::Preventif => 'Preventif',
            self::Pengadaan => 'Pengadaan',
            self::Anggaran => 'Anggaran',
            self::Kontrak => 'Kontrak',
            self::Kepatuhan => 'Kepatuhan',
        };
    }
}
