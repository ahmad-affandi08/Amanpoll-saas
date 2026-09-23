<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Domain\Enums;

/**
 * Jenis perpindahan aset.
 *
 * Istilah lapangan "returnasi" sengaja tidak dijadikan case tersendiri karena
 * artinya sama dengan Pengembalian; dua nama untuk satu hal hanya membuat
 * laporan terpecah.
 */
enum JenisPermintaanMutasiAset: string
{
    case AntarLokasi = 'AntarLokasi';
    case AntarUnit = 'AntarUnit';
    case Peminjaman = 'Peminjaman';
    case Pengembalian = 'Pengembalian';
    case Reposisi = 'Reposisi';
    case Akuisisi = 'Akuisisi';

    public function label(): string
    {
        return match ($this) {
            self::AntarLokasi => 'Antar Lokasi',
            self::AntarUnit => 'Antar Unit',
            self::Peminjaman => 'Peminjaman',
            self::Pengembalian => 'Pengembalian (Returnasi)',
            self::Reposisi => 'Reposisi',
            self::Akuisisi => 'Akuisisi',
        };
    }
}
