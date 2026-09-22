<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\ValueObjects;

/**
 * Satu-satunya tempat sumber dan medium satu kedatangan disimpulkan. Perekam
 * kunjungan dan pembaca sentuhan memakainya bersama, supaya sentuhan pertama
 * yang tercatat dan yang dibaca ulang tidak pernah berselisih (MARKETING.md 14).
 */
final class AsalKunjungan
{
    public const LANGSUNG = 'direct';

    public const RUJUKAN = 'referral';

    public static function sumber(?string $utmSource, ?string $referrer): string
    {
        if ($utmSource !== null && $utmSource !== '') {
            return $utmSource;
        }

        if ($referrer === null || $referrer === '') {
            return self::LANGSUNG;
        }

        $host = parse_url($referrer, PHP_URL_HOST);

        return is_string($host) && $host !== '' ? $host : self::LANGSUNG;
    }

    public static function medium(?string $utmMedium, ?string $utmSource, ?string $referrer): string
    {
        if ($utmMedium !== null && $utmMedium !== '') {
            return $utmMedium;
        }

        return $utmSource === null && $referrer === null ? self::LANGSUNG : self::RUJUKAN;
    }
}
