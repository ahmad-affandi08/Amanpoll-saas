<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Domain\Contracts;

use App\Domain\Pelaporan\Domain\ValueObjects\FilterMetrik;
use App\Domain\Pelaporan\Domain\ValueObjects\HasilKpi;

/**
 * Penyedia yang beberapa KPI-nya berbagi satu dataset (mis. sesi downtime).
 *
 * LayananMetrik memanggil hitungBanyak() sekali untuk seluruh KPI penyedia ini
 * yang diminta pada satu permintaan, sehingga dataset bersamanya cukup diambil
 * sekali. Hasilnya wajib identik dengan memanggil hitung() per kunci.
 */
interface PenyediaKpiBerkelompok extends PenyediaKpi
{
    /**
     * @param  list<string>  $kunci
     * @return array<string, HasilKpi>
     */
    public function hitungBanyak(array $kunci, FilterMetrik $filter): array;
}
