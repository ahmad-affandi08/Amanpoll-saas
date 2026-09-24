<?php

declare(strict_types=1);

namespace Tests\Dukungan;

use GdImage;
use RuntimeException;

/**
 * Pembuat gambar uji untuk mesin kompresi berkas (PRD 11.1): JPEG dengan EXIF
 * Orientation dan lokasi GPS, PNG transparan, dan GIF beranimasi.
 */
final class GambarUji
{
    /**
     * JPEG bergradasi dengan kotak merah di pojok kiri atas (dalam orientasi
     * tersimpan), agar arah rotasi dapat diperiksa sesudah diluruskan.
     */
    public static function jpeg(int $lebar, int $tinggi, int $kualitas = 95): string
    {
        $gambar = self::kanvas($lebar, $tinggi);
        $langkah = max(1, intdiv($lebar, 120));
        for ($x = 0; $x < $lebar; $x += $langkah) {
            $warna = self::warna($gambar, 40 + intdiv($x * 150, $lebar), 90, 200 - intdiv($x * 120, $lebar));
            imagefilledrectangle($gambar, $x, 0, $x + $langkah - 1, $tinggi - 1, $warna);
        }
        for ($i = 0; $i < 12; $i++) {
            imagefilledellipse($gambar, intdiv($lebar * ($i + 1), 13), intdiv($tinggi, 2), intdiv($lebar, 10), intdiv($tinggi, 3), self::warna($gambar, 250, 220, 30 + $i * 15));
        }
        imagefilledrectangle($gambar, 0, 0, intdiv($lebar, 5), intdiv($tinggi, 5), self::warna($gambar, 255, 0, 0));

        return self::keJpeg($gambar, $kualitas);
    }

    /** JPEG berisi derau berkualitas rendah: WebP q80-nya pasti lebih besar. */
    public static function jpegDerau(int $lebar = 400, int $tinggi = 300, int $kualitas = 20): string
    {
        $gambar = self::kanvas($lebar, $tinggi);
        mt_srand(42);
        for ($y = 0; $y < $tinggi; $y += 2) {
            for ($x = 0; $x < $lebar; $x += 2) {
                imagefilledrectangle($gambar, $x, $y, $x + 1, $y + 1, self::warna($gambar, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255)));
            }
        }

        return self::keJpeg($gambar, $kualitas);
    }

    /**
     * Menyisipkan segmen APP1 EXIF (Orientation + GPSLatitude) tepat sesudah SOI.
     */
    public static function denganExif(string $jpeg, int $orientasi, bool $denganGps = true): string
    {
        $entriIfd0 = [pack('vvVv', 0x0112, 3, 1, $orientasi)."\0\0"];
        if ($denganGps) {
            $entriIfd0[] = pack('vvVV', 0x8825, 4, 1, 8 + 2 + 2 * 12 + 4);
        }
        $ifd0 = pack('v', count($entriIfd0)).implode('', $entriIfd0).pack('V', 0);

        $tiff = "II*\0".pack('V', 8).$ifd0;
        if ($denganGps) {
            $offsetData = strlen($tiff) + 2 + 2 * 12 + 4;
            $tiff .= pack('v', 2)
                .pack('vvV', 0x0001, 2, 2)."S\0\0\0"
                .pack('vvVV', 0x0002, 5, 3, $offsetData)
                .pack('V', 0)
                .pack('VVVVVV', 6, 1, 10, 1, 3000, 100);
        }

        $isi = "Exif\0\0".$tiff;

        return "\xFF\xD8\xFF\xE1".pack('n', strlen($isi) + 2).$isi.substr($jpeg, 2);
    }

    /**
     * PNG truecolor bergaya foto (gradasi berderau): separuh kiri transparan
     * penuh, separuh kanan pekat. PNG-nya jauh lebih besar dari WebP-nya.
     */
    public static function pngTransparan(int $lebar = 800, int $tinggi = 600): string
    {
        $gambar = self::kanvas($lebar, $tinggi);
        imagealphablending($gambar, false);
        imagesavealpha($gambar, true);
        imagefilledrectangle($gambar, 0, 0, $lebar - 1, $tinggi - 1, self::warna($gambar, 0, 0, 0, 127));
        mt_srand(7);
        for ($y = 0; $y < $tinggi; $y += 2) {
            for ($x = intdiv($lebar, 2); $x < $lebar; $x += 2) {
                $derau = mt_rand(-12, 12);
                $warna = self::warna($gambar, 30 + $derau + intdiv($x * 60, $lebar), 80 + $derau + intdiv($y * 100, $tinggi), 200 + intdiv($derau, 2));
                imagefilledrectangle($gambar, $x, $y, $x + 1, $y + 1, $warna);
            }
        }

        ob_start();
        imagepng($gambar, null, 9);

        return (string) ob_get_clean();
    }

    /**
     * GIF dua bingkai beranimasi dari gambar bergradasi: bingkai pertamanya saja
     * sebagai WebP jauh lebih kecil, jadi hanya deteksi animasi yang menahannya.
     */
    public static function gifAnimasi(int $lebar = 240, int $tinggi = 180): string
    {
        $gambar = self::kanvas($lebar, $tinggi);
        for ($y = 0; $y < $tinggi; $y++) {
            imageline($gambar, 0, $y, $lebar - 1, $y, self::warna($gambar, intdiv($y * 255, $tinggi), 120, 255 - intdiv($y * 255, $tinggi)));
        }
        for ($x = 0; $x < $lebar; $x += 3) {
            imageline($gambar, $x, 0, $lebar - 1 - $x, $tinggi - 1, self::warna($gambar, 250, intdiv($x * 255, $lebar), 20));
        }
        imagetruecolortopalette($gambar, true, 256);
        ob_start();
        imagegif($gambar);
        $gif = (string) ob_get_clean();

        // Kepala (6) + logical screen descriptor (7) + tabel warna global.
        $bendera = ord($gif[10]);
        $panjangKepala = 13 + (($bendera & 0x80) !== 0 ? 3 * (2 ** (($bendera & 0x07) + 1)) : 0);
        $bingkai = substr($gif, $panjangKepala, -1);
        if ($bingkai[0] !== "\x2C") {
            throw new RuntimeException('GIF uji tidak diawali deskriptor gambar.');
        }
        $kendali = "\x21\xF9\x04\x00\x0A\x00\x00\x00";

        return substr($gif, 0, $panjangKepala)
            ."\x21\xFF\x0BNETSCAPE2.0\x03\x01\x00\x00\x00"
            .$kendali.$bingkai.$kendali.$bingkai
            ."\x3B";
    }

    public static function gambarDari(string $isi): GdImage
    {
        $gambar = imagecreatefromstring($isi);
        if (! $gambar instanceof GdImage) {
            throw new RuntimeException('Isi bukan gambar yang dapat dibaca GD.');
        }

        return $gambar;
    }

    /** @return array{0: int, 1: int, 2: int, 3: int} merah, hijau, biru, alfa (0 pekat .. 127 transparan). */
    public static function piksel(GdImage $gambar, int $x, int $y): array
    {
        $warna = imagecolorsforindex($gambar, imagecolorat($gambar, $x, $y));

        return [$warna['red'], $warna['green'], $warna['blue'], $warna['alpha']];
    }

    private static function kanvas(int $lebar, int $tinggi): GdImage
    {
        $gambar = imagecreatetruecolor($lebar, $tinggi);
        if (! $gambar instanceof GdImage) {
            throw new RuntimeException('Kanvas uji gagal dibuat.');
        }

        return $gambar;
    }

    private static function warna(GdImage $gambar, int $merah, int $hijau, int $biru, int $alfa = 0): int
    {
        $warna = imagecolorallocatealpha($gambar, $merah, $hijau, $biru, $alfa);
        if ($warna === false) {
            throw new RuntimeException('Warna uji gagal dialokasikan.');
        }

        return $warna;
    }

    private static function keJpeg(GdImage $gambar, int $kualitas): string
    {
        ob_start();
        imagejpeg($gambar, null, $kualitas);

        return (string) ob_get_clean();
    }
}
