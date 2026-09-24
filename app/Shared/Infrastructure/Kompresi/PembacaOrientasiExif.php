<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Kompresi;

/**
 * Membaca tag Orientation (0x0112) EXIF dari JPEG tanpa ekstensi `exif`,
 * yang tidak dijamin terpasang di shared hosting. Hanya segmen kepala yang
 * dibaca; data gambar tidak disentuh.
 */
final class PembacaOrientasiExif
{
    private const TAG_ORIENTASI = 0x0112;

    /** @return int 1..8; 1 bila tidak ada atau tidak terbaca. */
    public static function baca(string $lokasi): int
    {
        $aliran = fopen($lokasi, 'rb');
        if ($aliran === false) {
            return 1;
        }

        try {
            if (fread($aliran, 2) !== "\xFF\xD8") {
                return 1;
            }

            while (! feof($aliran)) {
                $penanda = PenandaJpeg::bacaBerikutnya($aliran);
                if ($penanda === null || PenandaJpeg::akhirKepala($penanda)) {
                    return 1;
                }
                if (PenandaJpeg::tanpaPanjang($penanda)) {
                    continue;
                }

                $panjang = PenandaJpeg::bacaPanjang($aliran);
                if ($panjang === null) {
                    return 1;
                }

                if ($penanda === 0xE1 && $panjang > 0) {
                    $data = (string) fread($aliran, $panjang);
                    if (str_starts_with($data, "Exif\0\0")) {
                        return self::dariTiff(substr($data, 6)) ?? 1;
                    }

                    continue;
                }

                fseek($aliran, $panjang, SEEK_CUR);
            }
        } finally {
            fclose($aliran);
        }

        return 1;
    }

    private static function dariTiff(string $tiff): ?int
    {
        $urutan = substr($tiff, 0, 2);
        if (strlen($tiff) < 8 || ($urutan !== 'II' && $urutan !== 'MM')) {
            return null;
        }
        $kecilDulu = $urutan === 'II';

        $ifd = self::angka($tiff, 4, 4, $kecilDulu);
        $jumlah = $ifd === null ? null : self::angka($tiff, $ifd, 2, $kecilDulu);
        if ($ifd === null || $jumlah === null) {
            return null;
        }

        for ($i = 0; $i < $jumlah; $i++) {
            $posisi = $ifd + 2 + $i * 12;
            if (self::angka($tiff, $posisi, 2, $kecilDulu) === self::TAG_ORIENTASI) {
                $nilai = self::angka($tiff, $posisi + 8, 2, $kecilDulu);

                return $nilai !== null && $nilai >= 1 && $nilai <= 8 ? $nilai : 1;
            }
        }

        return null;
    }

    private static function angka(string $data, int $posisi, int $lebar, bool $kecilDulu): ?int
    {
        if ($posisi < 0 || $posisi + $lebar > strlen($data)) {
            return null;
        }

        $format = $lebar === 2 ? ($kecilDulu ? 'v' : 'n') : ($kecilDulu ? 'V' : 'N');
        $hasil = unpack($format, substr($data, $posisi, $lebar));

        return $hasil === false ? null : (int) $hasil[1];
    }
}
