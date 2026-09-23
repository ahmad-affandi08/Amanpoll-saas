<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Ekspor;


/**
 * Penulis berkas ekspor untuk satu format (21.05).
 *
 * `$baris` sengaja iterable, bukan array: ekspor daftar operasional dapat
 * mencapai puluhan ribu baris, dan menyusunnya lebih dulu di memori akan
 * menghabiskan jatah PHP di hosting bersama. Array tetap diterima, sehingga
 * pemanggil yang barisnya memang sedikit tidak perlu berubah.
 */
interface PenulisEkspor
{
    public function format(): FormatEkspor;

    /**
     * @param  list<string>  $kepala
     * @param  iterable<int, list<string|float|int|null>>  $baris
     * @param  array<string, string>  $meta  keterangan yang dicetak di berkas, mis. judul dan rentang
     */
    public function tulis(string $pathLokal, array $kepala, iterable $baris, array $meta): void;
}
