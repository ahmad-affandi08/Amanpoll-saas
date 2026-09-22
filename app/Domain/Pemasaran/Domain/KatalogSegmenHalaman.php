<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain;

/** Segmen awal halaman industri (MARKETING.md 8). */
final class KatalogSegmenHalaman
{
    /** @return array<string, string> */
    public static function semua(): array
    {
        return [
            'manufaktur' => 'Manufaktur',
            'hotel' => 'Hotel',
            'property' => 'Property / Facility',
            'healthcare' => 'Healthcare',
            'workshop' => 'Workshop',
            'pendidikan' => 'Pendidikan',
            'retail' => 'Retail Multi-Cabang',
            'logistik' => 'Logistik',
            'general' => 'General Business',
        ];
    }

    /** @return list<string> */
    public static function kode(): array
    {
        return array_keys(self::semua());
    }
}
