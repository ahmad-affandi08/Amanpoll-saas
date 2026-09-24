<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Kompresi;

use RuntimeException;

/** Berkas kerja sementara milik mesin kompresi; pemanggil yang menghapusnya. */
final class BerkasSementara
{
    public static function buat(): string
    {
        $lokasi = tempnam(sys_get_temp_dir(), 'amanpoll-kompresi-');
        if ($lokasi === false) {
            throw new RuntimeException('Tidak dapat membuat berkas sementara untuk kompresi.');
        }

        return $lokasi;
    }

    public static function hapus(?string $lokasi): void
    {
        if ($lokasi !== null && is_file($lokasi)) {
            unlink($lokasi);
        }
    }

    public static function ukuran(string $lokasi): int
    {
        clearstatcache(true, $lokasi);
        $ukuran = filesize($lokasi);
        if ($ukuran === false) {
            throw new RuntimeException('Ukuran berkas tidak dapat dibaca.');
        }

        return $ukuran;
    }
}
