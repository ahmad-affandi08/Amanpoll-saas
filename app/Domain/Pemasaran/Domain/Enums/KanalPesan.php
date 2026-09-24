<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Enums;

use App\Shared\Domain\ValueObjects\NomorWhatsApp;

/** Kanal tempat satu kontak dapat dihubungi, dan cara menormalkan alamatnya (MARKETING.md 16, 27). */
enum KanalPesan: string
{
    case Email = 'Email';
    case WhatsApp = 'WhatsApp';

    /** Bentuk baku yang disimpan dan dibandingkan; tanpa ini "0812-3456" dan "62812 3456" jadi dua orang. */
    public function normalkan(string $kontak): string
    {
        return match ($this) {
            self::Email => mb_strtolower(trim($kontak)),
            self::WhatsApp => $this->nomorBaku($kontak),
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Email => 'Email',
            self::WhatsApp => 'WhatsApp',
        };
    }

    /** Nomor Indonesia ditulis dalam banyak bentuk; semuanya dibawa ke E.164 tanpa tanda plus. */
    private function nomorBaku(string $nomor): string
    {
        return NomorWhatsApp::baku($nomor);
    }
}
