<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Kompresi;

/** Pembacaan penanda segmen JPEG, dipakai pembaca orientasi dan pembersih metadata. */
final class PenandaJpeg
{
    /**
     * Kode penanda berikutnya (byte kedua sesudah 0xFF), melewati byte pengisi 0xFF.
     *
     * @param  resource  $aliran
     */
    public static function bacaBerikutnya($aliran): ?int
    {
        $byte = fread($aliran, 1);
        if ($byte !== "\xFF") {
            return null;
        }

        do {
            $byte = fread($aliran, 1);
        } while ($byte === "\xFF");

        return $byte === false || $byte === '' ? null : ord($byte);
    }

    /**
     * Panjang isi segmen, tanpa dua byte panjang itu sendiri.
     *
     * @param  resource  $aliran
     */
    public static function bacaPanjang($aliran): ?int
    {
        $data = fread($aliran, 2);
        if ($data === false || strlen($data) < 2) {
            return null;
        }
        $hasil = unpack('n', $data);
        $panjang = $hasil === false ? 0 : (int) $hasil[1];

        return $panjang < 2 ? null : $panjang - 2;
    }

    /** SOS atau EOI: segmen kepala sudah habis. */
    public static function akhirKepala(int $penanda): bool
    {
        return $penanda === 0xDA || $penanda === 0xD9;
    }

    /** SOI, TEM, dan RSTn tidak membawa panjang. */
    public static function tanpaPanjang(int $penanda): bool
    {
        return $penanda === 0xD8 || $penanda === 0x01 || ($penanda >= 0xD0 && $penanda <= 0xD7);
    }
}
