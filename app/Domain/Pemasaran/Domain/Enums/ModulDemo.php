<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Enums;

/** Modul yang boleh ditampilkan di dalam demo (MARKETING.md 11, 34.1). */
enum ModulDemo: string
{
    case Aset = 'Aset';
    case PerintahKerja = 'PerintahKerja';
    case Preventif = 'Preventif';
    case Persediaan = 'Persediaan';
    case Kalibrasi = 'Kalibrasi';
    case Pengadaan = 'Pengadaan';
}
