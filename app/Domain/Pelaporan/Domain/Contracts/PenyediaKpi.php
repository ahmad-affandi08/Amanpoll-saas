<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Domain\Contracts;

use App\Domain\Pelaporan\Domain\ValueObjects\FilterMetrik;
use App\Domain\Pelaporan\Domain\ValueObjects\HasilKpi;

/**
 * Sumber perhitungan untuk sekelompok KPI (21.01).
 *
 * Satu penyedia memegang satu kelompok dari KatalogKpi.
 */
interface PenyediaKpi
{
    /** @return list<string> kunci KPI yang dilayani penyedia ini */
    public function kunciDilayani(): array;

    public function hitung(string $kunci, FilterMetrik $filter): HasilKpi;
}
