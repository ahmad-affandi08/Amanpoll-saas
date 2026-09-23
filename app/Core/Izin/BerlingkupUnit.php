<?php

declare(strict_types=1);

namespace App\Core\Izin;

/**
 * Model yang datanya dibatasi unit atau ruangan penggunanya.
 *
 * Dinyatakan sebagai antarmuka, bukan hanya method di trait, supaya
 * ScopeLingkup dapat memastikan modelnya memang menyediakan petanya sebelum
 * memanggilnya -- model lain yang kebetulan ikut terpasang tidak diam-diam
 * lolos tanpa saringan.
 */
interface BerlingkupUnit
{
    /**
     * Peta kolom penentu lingkup: nama kolom => daftar pembanding.
     *
     * @return array<string, 'unit'|'lokasi'>
     */
    public function kolomLingkup(): array;
}
