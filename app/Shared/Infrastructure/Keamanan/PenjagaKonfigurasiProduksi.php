<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Keamanan;

use Illuminate\Contracts\Foundation\Application;
use RuntimeException;

/** Menolak konfigurasi produksi yang membocorkan isi aplikasi (24). */
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
