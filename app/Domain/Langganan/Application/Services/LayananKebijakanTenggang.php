<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Application\Services;

/** Kebijakan masa tenggang (22.04). */
final class LayananKebijakanTenggang
{
    private const BAWAAN_HARI_TENGGANG = 7;

    public function hariTenggang(): int
    {
        $nilai = config('amanpoll.langganan.hari_tenggang', self::BAWAAN_HARI_TENGGANG);

        return max(0, (int) $nilai);
    }

    public function hariUjiCoba(): int
    {
        $nilai = config('amanpoll.langganan.hari_uji_coba', 14);

        return max(0, (int) $nilai);
    }

    /** Hari sejak terbit sampai tagihan jatuh tempo. */
    public function hariJatuhTempo(): int
    {
        $nilai = config('amanpoll.langganan.hari_jatuh_tempo', 14);

        return max(1, (int) $nilai);
    }
}
