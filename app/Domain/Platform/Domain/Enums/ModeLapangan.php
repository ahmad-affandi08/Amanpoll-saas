<?php

declare(strict_types=1);

namespace App\Domain\Platform\Domain\Enums;

/**
 * Tampilan Mode Lapangan yang ditandai pada sebuah Peran (PRD 8.20).
 *
 * Nilai kosong pada `Peran.TampilanLapangan` berarti peran meja: pemegangnya
 * memakai dasbor web. Penentuan pengguna lapangan selalu membaca penanda ini,
 * tidak pernah kode peran harfiah, karena tenant bebas mengganti nama dan kode perannya.
 */
enum ModeLapangan: string
{
    case Teknisi = 'Teknisi';
    case Pelapor = 'Pelapor';
}
