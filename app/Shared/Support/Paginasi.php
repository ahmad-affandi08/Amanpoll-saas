<?php

declare(strict_types=1);

namespace App\Shared\Support;

final class Paginasi
{
    public static function perHalaman(?int $nilai, int $default = 25, int $maksimum = 100): int
    {
        return min(max($nilai ?? $default, 1), $maksimum);
    }
}
