<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence;

use Carbon\CarbonImmutable;
use DateTimeInterface;

/**
 * Objek waktu yang diikat ke kueri dikirim sebagai momen UTC.
 *
 * `Connection::prepareBindings()` memformat jam dinding objek waktu apa
 * adanya, tanpa melihat zonanya. `whereBetween('DibuatPada', [$awalHariWit,
 * ...])` dengan batas hari Jayapura karena itu menyaring sembilan jam terlalu
 * lambat, dan `update(['SelesaiPada' => $waktuBerzona])` lewat query builder
 * menulis jam yang salah tanpa melewati normalisasi model. Kolom waktu
 * disimpan UTC dan sesi basis data berjalan di +00:00, jadi objek waktu yang
 * terikat harus berbicara UTC juga.
 *
 * Tanggal kalender tetap dikirim sebagai teks `Y-m-d`, bukan objek waktu --
 * begitulah seluruh kueri kolom `date` di aplikasi ini ditulis. Nilai yang
 * sudah UTC, termasuk tengah malam UTC dari `KalenderOrganisasi::hariIni()`,
 * tidak berubah.
 */
trait MengikatWaktuDalamUtc
{
    /**
     * @param  array<array-key, mixed>  $bindings
     * @return array<array-key, mixed>
     */
    public function prepareBindings(array $bindings)
    {
        foreach ($bindings as $key => $value) {
            if ($value instanceof DateTimeInterface && $value->getOffset() !== 0) {
                $bindings[$key] = CarbonImmutable::instance($value)->utc();
            }
        }

        return parent::prepareBindings($bindings);
    }
}
