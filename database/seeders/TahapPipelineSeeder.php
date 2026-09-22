<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Pemasaran\Domain\KatalogTahapPipeline;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\TahapPipeline;
use Illuminate\Database\Seeder;

/**
 * Menyemai tahap pipeline bawaan (MARKETING.md 5.3).
 *
 * Nama dan urutannya tidak ditimpa bila tahapnya sudah ada: itu kebijakan
 * penjualan yang boleh diubah dari konsol, dan seeder tidak berhak
 * mengembalikannya setiap deployment.
 */
final class TahapPipelineSeeder extends Seeder
{
    public function run(): void
    {
        foreach (KatalogTahapPipeline::bawaan() as $tahap) {
            TahapPipeline::query()->firstOrCreate(['Kode' => $tahap['Kode']], [
                'Nama' => $tahap['Nama'],
                'Urutan' => $tahap['Urutan'],
                'TahapAkhir' => $tahap['TahapAkhir'],
                'DianggapMenang' => $tahap['DianggapMenang'],
            ]);
        }
    }
}
