<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence;

use Illuminate\Database\MariaDbConnection;

/** Koneksi MariaDB yang mengikat objek waktu sebagai UTC; lihat MengikatWaktuDalamUtc. */
final class KoneksiMariaDbUtc extends MariaDbConnection
{
    use MengikatWaktuDalamUtc;
}
