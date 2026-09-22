<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Enums;

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
        // Awalan 0 diganti 62, awalan 8 dianggap sudah tanpa nol, dan pemisah apa pun dibuang.
        $angka = preg_replace('/\D+/', '', $nomor) ?? '';

        if ($angka === '') {
            return '';
        }

        if (str_starts_with($angka, '0')) {
            return '62'.ltrim($angka, '0');
        }

        if (str_starts_with($angka, '8')) {
            return '62'.$angka;
        }

        return $angka;
    }
}
