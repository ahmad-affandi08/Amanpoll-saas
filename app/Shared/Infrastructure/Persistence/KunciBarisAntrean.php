<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\MySqlConnection;

/**
 * Klausa kunci untuk mengambil sekumpulan baris antrean yang siap diproses.
 *
 * `FOR UPDATE SKIP LOCKED` membuat worker kedua melewati baris yang sedang dipegang
 * worker pertama, alih-alih menunggu kuncinya dilepas. Klausa ini baru ada di
 * MySQL 8.0.1 dan MariaDB 10.6; server yang lebih tua menolak sintaksnya, jadi di
 * sana dipakai `FOR UPDATE` biasa (satu worker tetap aman karena jadwalnya tidak
 * bertumpuk, hanya worker kedua yang akan menunggu).
 */
final class KunciBarisAntrean
{
    public static function klausa(ConnectionInterface $koneksi): string|bool
    {
        return self::mendukungSkipLocked($koneksi) ? 'for update skip locked' : true;
    }

    public static function mendukungSkipLocked(ConnectionInterface $koneksi): bool
    {
        // MariaDbConnection turunan MySqlConnection, jadi keduanya tertangkap di sini.
        if (! $koneksi instanceof MySqlConnection) {
            return false;
        }

        return self::versiMendukung($koneksi->getServerVersion(), $koneksi->isMaria());
    }

    /** Dipisah supaya aturan versinya dapat diuji tanpa server yang berbeda-beda. */
    public static function versiMendukung(string $versiServer, bool $mariaDb): bool
    {
        // Pustaka klien lama melaporkan MariaDB sebagai "5.5.5-10.11.6-MariaDB".
        $versi = preg_replace('/^5\.5\.5-/', '', $versiServer) ?? $versiServer;

        if (preg_match('/^(\d+)\.(\d+)\.(\d+)/', $versi, $cocok) !== 1) {
            return false;
        }

        $nomor = "{$cocok[1]}.{$cocok[2]}.{$cocok[3]}";

        return $mariaDb || str_contains(strtolower($versiServer), 'mariadb')
            ? version_compare($nomor, '10.6.0', '>=')
            : version_compare($nomor, '8.0.1', '>=');
    }
}
