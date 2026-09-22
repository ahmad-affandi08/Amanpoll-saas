<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence;

/**
 * Batas atas daftar yang masih dipaginasi di browser (FASE 25.01).
 *
 * Halaman yang memakai komponen DataTable memaginasi, mencari, dan mengurutkan
 * di sisi klien, sehingga server terlanjur mengirim seluruh baris. Selama
 * halaman-halaman itu belum dipindahkan ke paginasi server, batas ini menahan
 * satu tabel yang tumbuh dari menghabiskan memori PHP dan memori browser
 * sekaligus.
 *
 * Batasnya bukan paginasi dan tidak berpura-pura menjadi paginasi: baris di
 * luar batas memang tidak dikirim, dan DataTable menyatakannya di layar supaya
 * tidak ada yang mengira sedang melihat seluruh data.
 */
final class BatasDaftar
{
    /** Nilainya dikunci bersama `BATAS_DAFTAR` di resources/js/lib/batas.ts. */
    public const MAKS = 500;
}
