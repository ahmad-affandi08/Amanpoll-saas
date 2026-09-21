<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Keamanan;

use Illuminate\Contracts\Foundation\Application;
use RuntimeException;

/**
 * Menolak konfigurasi produksi yang membocorkan isi aplikasi (24).
 *
 * Diperiksa saat boot, bukan diserahkan ke daftar periksa penempatan, karena
 * salah satu setelan ini yang lolos ke produksi tidak menimbulkan gejala apa
 * pun sampai ada permintaan yang gagal — dan saat itu halaman kesalahannya
 * memuat jejak tumpukan, isi environment, dan kredensial basis data.
 */
final class PenjagaKonfigurasiProduksi
{
    public static function periksa(Application $aplikasi): void
    {
        if (! $aplikasi->environment('production')) {
            return;
        }

        $pelanggaran = [];

        if (config('app.debug') === true) {
            $pelanggaran[] = 'APP_DEBUG harus false di produksi.';
        }

        if (config('session.secure') !== true) {
            $pelanggaran[] = 'SESSION_SECURE_COOKIE harus true di produksi.';
        }

        if (config('session.http_only') !== true) {
            $pelanggaran[] = 'SESSION_HTTP_ONLY harus true di produksi.';
        }

        if ($pelanggaran !== []) {
            throw new RuntimeException(
                'Konfigurasi produksi tidak aman: '.implode(' ', $pelanggaran),
            );
        }
    }
}
