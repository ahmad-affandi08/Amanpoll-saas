<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

/** Satu perangkap honeypot untuk seluruh endpoint publik (MARKETING.md 10). */
final class PerangkapSpam
{
    /** Nama field perangkap; disembunyikan di formulir, hanya robot yang mengisinya. */
    public const FIELD = 'situs_perusahaan';

    /** @param array<string, mixed> $masukan */
    public function terperangkap(array $masukan): bool
    {
        $nilai = $masukan[self::FIELD] ?? null;

        return is_string($nilai) && trim($nilai) !== '';
    }
}
