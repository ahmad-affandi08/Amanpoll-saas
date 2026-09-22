<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Pemasaran\Domain\KatalogFiturPlatform;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\FiturPlatform;
use Illuminate\Database\Seeder;

/**
 * Menyemai flag modul pemasaran dalam keadaan mati (MARKETING.md 31).
 *
 * Mati, bukan hidup: modul baru dinyalakan secara sadar dari konsol, bukan ikut
 * menyala begitu deployment selesai. Status flag yang sudah ada tidak pernah
 * ditimpa, supaya seeder aman dijalankan ulang di produksi.
 */
final class FiturPlatformSeeder extends Seeder
{
    public function run(): void
    {
        foreach (KatalogFiturPlatform::semua() as $kode => $definisi) {
            FiturPlatform::query()->firstOrCreate(
                ['Kode' => $kode],
                [
                    'Nama' => $definisi['nama'],
                    'Keterangan' => $definisi['keterangan'],
                    'Aktif' => false,
                ],
            );
        }
    }
}
