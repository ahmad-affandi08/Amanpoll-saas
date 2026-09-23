<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence;

use Illuminate\Database\MySqlConnection;

/** Koneksi MySQL yang mengikat objek waktu sebagai UTC; lihat MengikatWaktuDalamUtc. */
final class KoneksiMySqlUtc extends MySqlConnection
{
    use MengikatWaktuDalamUtc;
}
