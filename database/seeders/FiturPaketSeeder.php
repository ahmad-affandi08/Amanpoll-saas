<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Langganan\Domain\KatalogFitur;
use App\Domain\Langganan\Domain\ValueObjects\DefinisiFitur;
use App\Domain\Langganan\Infrastructure\Persistence\Models\FiturPaket;
use Illuminate\Database\Seeder;

/**
 * Menyemai master fitur paket dari KatalogFitur (22.01).
 *
 * Seeder ini menyalin satu arah: katalog kode adalah sumbernya, tabel hanyalah
 * cerminannya. Baris yang sudah ada diperbarui nama dan tipenya, tetapi Id-nya
 * dipertahankan karena PaketFitur menunjuk ke sana — menghapus dan membuat
 * ulang akan memutus entitlement setiap pelanggan.
 */
final class FiturPaketSeeder extends Seeder
{
    public function run(): void
    {
        foreach (KatalogFitur::semua() as $kode => $definisi) {
            $this->semai($kode, $definisi);
        }
    }

    private function semai(string $kode, DefinisiFitur $definisi): void
    {
        $fitur = FiturPaket::query()->where('Kode', $kode)->first() ?? new FiturPaket;

        $fitur->fill([
            'Kode' => $kode,
            'Nama' => $definisi->nama,
            'Deskripsi' => $definisi->deskripsi,
            'TipeBatas' => $definisi->tipeBatas->value,
        ]);

        $fitur->save();
    }
}
