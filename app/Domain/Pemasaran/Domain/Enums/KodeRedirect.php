<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Enums;

/**
 * Kode redirect yang didukung situs publik (MARKETING.md 9).
 *
 * Bernilai teks meski isinya angka, mengikuti kosakata domain lainnya yang
 * seluruhnya tersimpan sebagai teks.
 */
enum KodeRedirect: string
{
    case Permanen = '301';
    case Sementara = '302';
    case Hilang = '410';

    public function statusHttp(): int
    {
        return (int) $this->value;
    }

    /** 410 menyatakan sumber daya hilang permanen, jadi ia tidak punya tujuan. */
    public function butuhTujuan(): bool
    {
        return $this !== self::Hilang;
    }
}
