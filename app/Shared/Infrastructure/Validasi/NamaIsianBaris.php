<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Validasi;

use Illuminate\Support\Str;

/**
 * Nama isian bertanda bintang (`Detail.*.HargaSatuan`) untuk pesan validasi.
 *
 * Laravel menampilkan isian hasil pemekaran bintang apa adanya, sehingga
 * pengguna membaca "Detail.0.HargaSatuan wajib diisi." Di sini segmen terakhir
 * dijadikan kata dan indeksnya dijadikan nomor baris yang dihitung dari satu.
 */
final class NamaIsianBaris
{
    public static function tampilkan(string $atribut): string
    {
        $segmen = explode('.', $atribut);
        $nama = null;
        $indeks = null;

        foreach ($segmen as $satu) {
            if (ctype_digit($satu)) {
                $indeks = (int) $satu;
            } else {
                $nama = $satu;
            }
        }

        $kata = str_replace('_', ' ', Str::snake($nama ?? $atribut));

        return $indeks === null ? $kata : "{$kata} baris ".($indeks + 1);
    }
}
