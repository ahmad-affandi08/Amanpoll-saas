<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Kompresi;

/**
 * Cara isi berkas disimpan di disk (kolom `Berkas.MetodeKompresi`, PRD 11.1).
 *
 * - `Tidak`: isi tersimpan sama dengan yang diterima pengguna saat mengunduh
 *   (untuk gambar: tanpa metadata).
 * - `Gzip`: isi tersimpan adalah gzip dari berkas asli; dibuka saat diunduh.
 * - `GambarUlang`: gambar dikodekan ulang ke WebP; pengguna menerima WebP-nya.
 */
enum MetodeKompresi: string
{
    case Tidak = 'Tidak';
    case Gzip = 'Gzip';
    case GambarUlang = 'GambarUlang';
}
