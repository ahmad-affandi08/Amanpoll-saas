<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Enums;

/** Metrik yang dapat dipasangi target kampanye (MARKETING.md 13). */
enum MetrikTargetKampanye: string
{
    case Visitor = 'Visitor';
    case Lead = 'Lead';
    case Trial = 'Trial';
    case Teraktivasi = 'Teraktivasi';
    case Bayar = 'Bayar';
    case Revenue = 'Revenue';

    /** Kolom padanannya di MetrikKampanye; itulah yang dibandingkan dengan targetnya. */
    public function kolomMetrik(): string
    {
        return $this->value;
    }

    public function satuanUang(): bool
    {
        return $this === self::Revenue;
    }
}
