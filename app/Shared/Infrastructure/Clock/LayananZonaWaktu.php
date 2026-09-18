<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Clock;

use Carbon\CarbonImmutable;
use DateTimeInterface;

/**
 * Titik tunggal konversi waktu Amanpoll: penyimpanan selalu UTC,
 * presentasi mengikuti zona waktu organisasi/lokasi. Jangan panggil
 * timezone hardcoded di feature; gunakan service ini.
 */
final class LayananZonaWaktu
{
    public function sekarangUtc(): CarbonImmutable
    {
        return CarbonImmutable::now('UTC');
    }

    public function keZonaWaktu(DateTimeInterface|string $waktuUtc, string $zonaWaktu): CarbonImmutable
    {
        return CarbonImmutable::parse($waktuUtc, 'UTC')->setTimezone($zonaWaktu);
    }

    public function keUtc(DateTimeInterface|string $waktuLokal, string $zonaWaktu): CarbonImmutable
    {
        return CarbonImmutable::parse($waktuLokal, $zonaWaktu)->setTimezone('UTC');
    }

    /**
     * Lokasi dapat menimpa zona waktu organisasi induknya; kembalikan yang berlaku.
     */
    public function zonaWaktuEfektif(?string $zonaWaktuLokasi, string $zonaWaktuOrganisasi): string
    {
        return $zonaWaktuLokasi ?: $zonaWaktuOrganisasi;
    }
}
