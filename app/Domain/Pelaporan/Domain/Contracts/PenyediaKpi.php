<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Domain\Contracts;

use App\Domain\Pelaporan\Domain\ValueObjects\FilterMetrik;
use App\Domain\Pelaporan\Domain\ValueObjects\HasilKpi;

/**
 * Sumber perhitungan untuk sekelompok KPI (21.01).
 *
 * Satu penyedia memegang satu kelompok dari KatalogKpi. Tes arsitektur menjaga
 * agar setiap kunci di katalog dilayani tepat satu penyedia dan tidak ada
 * penyedia yang mengaku melayani kunci di luar katalog, sehingga dasbor tidak
 * mungkin menampilkan KPI tanpa rumus terdokumentasi.
 */
interface PenyediaKpi
{
    /** @return list<string> kunci KPI yang dilayani penyedia ini */
    public function kunciDilayani(): array;

    public function hitung(string $kunci, FilterMetrik $filter): HasilKpi;
}
