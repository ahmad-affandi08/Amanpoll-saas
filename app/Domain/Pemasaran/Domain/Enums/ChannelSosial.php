<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Enums;

/** Kanal sosial yang didukung abstraksinya (MARKETING.md 18). */
enum ChannelSosial: string
{
    case LinkedIn = 'LinkedIn';
    case Instagram = 'Instagram';
    case Facebook = 'Facebook';
    case TikTok = 'TikTok';
    case YouTube = 'YouTube';
    case X = 'X';

    /** Nilai utm_source bawaan channel ini; dapat ditimpa per distribusi. */
    public function utmSource(): string
    {
        return mb_strtolower($this->value);
    }

    /** Channel yang menuntut media; caption saja akan ditolak penyedianya. */
    public function wajibMedia(): bool
    {
        return in_array($this, [self::Instagram, self::TikTok, self::YouTube], true);
    }
}
