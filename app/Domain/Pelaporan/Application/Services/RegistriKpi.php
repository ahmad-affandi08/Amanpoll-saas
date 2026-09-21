<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Application\Services;

use App\Domain\Pelaporan\Domain\Contracts\PenyediaKpi;
use App\Shared\Domain\Exceptions\DataTidakDitemukan;
use LogicException;

/**
 * Pemetaan kunci KPI ke penyedia yang menghitungnya.
 *
 * Pendaftaran ganda ditolak keras: dua penyedia yang mengaku menghitung KPI
 * yang sama berarti ada dua rumus untuk satu angka, dan itu persis kegagalan
 * yang ingin dicegah Gate 21.
 */
final class RegistriKpi
{
    /** @var array<string, PenyediaKpi> */
    private array $penyedia = [];

    public function daftarkan(PenyediaKpi $penyedia): void
    {
        foreach ($penyedia->kunciDilayani() as $kunci) {
            if (isset($this->penyedia[$kunci])) {
                throw new LogicException("KPI {$kunci} sudah didaftarkan penyedia lain.");
            }

            $this->penyedia[$kunci] = $penyedia;
        }
    }

    public function ada(string $kunci): bool
    {
        return isset($this->penyedia[$kunci]);
    }

    public function untuk(string $kunci): PenyediaKpi
    {
        return $this->penyedia[$kunci]
            ?? throw new DataTidakDitemukan("Tidak ada penyedia untuk KPI {$kunci}.");
    }

    /** @return list<string> */
    public function kunciTerdaftar(): array
    {
        return array_keys($this->penyedia);
    }
}
