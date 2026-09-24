<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObjects;

/**
 * Nomor telepon dalam bentuk yang diterima WhatsApp: format internasional tanpa tanda plus.
 *
 * Satu aturan untuk pemasaran (kontak prospek) dan notifikasi operasional (telepon pengguna),
 * supaya "0812-3456", "+62 812 3456", dan "62812-3456" selalu menjadi nomor yang sama.
 */
final class NomorWhatsApp
{
    /** Batas panjang E.164; di bawah batas bawah tidak ada nomor seluler yang sah. */
    private const PANJANG_MINIMUM = 9;

    private const PANJANG_MAKSIMUM = 15;

    /** Awalan 0 diganti 62, awalan 8 dianggap nomor Indonesia tanpa nol, dan pemisah apa pun dibuang. */
    public static function baku(string $nomor): string
    {
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

    /** Bentuk baku bila nomornya masuk akal untuk WhatsApp; null bila kosong atau jelas bukan nomor telepon. */
    public static function internasional(?string $nomor): ?string
    {
        // Huruf di dalam isian berarti bukan nomor telepon (mis. "belum ada"), bukan nomor yang perlu dibersihkan.
        if ($nomor === null || preg_match('/\p{L}/u', $nomor) === 1) {
            return null;
        }

        $baku = self::baku($nomor);
        $panjang = strlen($baku);

        return $panjang >= self::PANJANG_MINIMUM && $panjang <= self::PANJANG_MAKSIMUM && $baku[0] !== '0'
            ? $baku
            : null;
    }

    /** Nomor yang disamarkan untuk log dan pesan galat: hanya empat angka terakhir yang tersisa. */
    public static function samarkan(string $nomor): string
    {
        $baku = self::baku($nomor);

        return strlen($baku) <= 4 ? '****' : str_repeat('*', strlen($baku) - 4).substr($baku, -4);
    }
}
