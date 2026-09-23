<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Ekspor;

/**
 * Menetralkan injeksi rumus pada berkas ekspor (24).
 *
 * Isi ekspor berasal dari data tenant -- nama aset, gudang, penyedia. Satu
 * pengguna yang menamai asetnya `=cmd|' /c calc'!A0` dapat mengeksekusi
 * perintah di komputer rekan kerjanya yang membuka hasil ekspor.
 *
 * Ancamannya berbeda per format, dan itulah sebabnya penetralannya satu pintu:
 *
 * - CSV tidak punya tipe sel, jadi Excel menebak saat impor dan memperlakukan
 *   sel berawalan `=`, `+`, `-`, `@`, tab, atau carriage return sebagai rumus.
 * - XLSX punya tipe sel, sehingga sebagian besar nilai aman dengan sendirinya --
 *   tetapi OpenSpout mendeteksi awalan `=` dan menulisnya sebagai sel rumus
 *   `<f>` yang sungguhan, bukan teks.
 *
 * Kutip tunggal di depan adalah penanda "ini teks" yang dikenali Excel dan
 * LibreOffice, dan tidak ikut tampil di selnya.
 */
final class NetralkanRumus
{
    /** Berbahaya di CSV, yang tipenya ditebak saat impor. */
    private const AWALAN_CSV = "=+-@\t\r";

    /** Berbahaya di XLSX: hanya `=`, karena itu yang diubah OpenSpout menjadi sel rumus. */
    private const AWALAN_XLSX = '=';

    public static function untukCsv(mixed $nilai): mixed
    {
        return self::terapkan($nilai, self::AWALAN_CSV);
    }

    public static function untukXlsx(mixed $nilai): mixed
    {
        return self::terapkan($nilai, self::AWALAN_XLSX);
    }

    /**
     * @param  array<int|string, mixed>  $baris
     * @return array<int|string, mixed>
     */
    public static function barisCsv(array $baris): array
    {
        return array_map(static fn (mixed $nilai): mixed => self::untukCsv($nilai), $baris);
    }

    /**
     * @param  array<int|string, mixed>  $baris
     * @return array<int|string, mixed>
     */
    public static function barisXlsx(array $baris): array
    {
        return array_map(static fn (mixed $nilai): mixed => self::untukXlsx($nilai), $baris);
    }

    private static function terapkan(mixed $nilai, string $awalan): mixed
    {
        if (! is_string($nilai) || $nilai === '') {
            return $nilai;
        }

        return str_contains($awalan, $nilai[0]) ? "'".$nilai : $nilai;
    }
}
