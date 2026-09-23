<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Keempat seeder ini wajib di lingkungan mana pun: isinya kunci yang
     * dirujuk kode program, bukan data contoh. Tanpa IzinSeeder, pemeriksaan
     * otorisasi menolak semua orang.
     *
     * @var list<class-string<Seeder>>
     */
    private const SEEDER_WAJIB = [
        IzinSeeder::class,
        FiturPaketSeeder::class,
        FiturPlatformSeeder::class,
        TahapPipelineSeeder::class,
    ];

    /**
     * Seed the application's database.
     *
     * Data contoh sengaja ditinggalkan di produksi alih-alih menggagalkan
     * seluruh perintah: `db:seed --force` di sana tetap harus menyemai kunci
     * wajibnya sampai tuntas dan keluar bersih, supaya tidak ada yang mengira
     * deployment-nya gagal lalu memutarnya balik.
     */
    public function run(): void
    {
        $this->call(self::SEEDER_WAJIB);

        if (app()->environment('production')) {
            $this->command?->warn('Lingkungan produksi: DemoAwalSeeder dilewati.');

            return;
        }

        $this->call(DemoAwalSeeder::class);
    }
}
