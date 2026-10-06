<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Ekspor;

/**
 * Lambang Amanpoll untuk footer "Powered by Amanpoll" pada berkas ekspor.
 *
 * Berbeda dari LogoKopEkspor: berkas ini milik aplikasi sendiri (dipaketkan
 * di public/images/branding), bukan sesuatu yang disetel tenant lewat URL --
 * jadi tidak perlu penjagaan SSRF/MIME seperti logo organisasi di kop. Dibaca
 * sekali lalu disimpan di properti statis supaya satu proses PHP (mis. worker
 * antrean yang memproses banyak ekspor berturut-turut) tidak membaca berkas
 * yang sama dari disk berulang kali.
 */
final class LogoAmanpollEkspor
{
    private const PATH = 'images/branding/amanpoll-icon.png';

    private static ?string $dataUriTersimpan = null;

    private static bool $sudahDibaca = false;

    public static function dataUri(): ?string
    {
        if (self::$sudahDibaca) {
            return self::$dataUriTersimpan;
        }

        self::$sudahDibaca = true;
        self::$dataUriTersimpan = self::baca();

        return self::$dataUriTersimpan;
    }

    private static function baca(): ?string
    {
        $path = public_path(self::PATH);

        if (! is_file($path)) {
            return null;
        }

        $isi = @file_get_contents($path);

        if ($isi === false) {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode($isi);
    }
}
