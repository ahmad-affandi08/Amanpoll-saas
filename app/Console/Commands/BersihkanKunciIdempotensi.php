<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Core\Idempotensi\LayananIdempotensi;
use Illuminate\Console\Command;

/**
 * Membuang kunci idempotensi yang sudah lewat TTL supaya tabelnya tidak tumbuh
 * tanpa batas (19.07).
 */
final class BersihkanKunciIdempotensi extends Command
{
    protected $signature = 'idempotensi:bersihkan';

    protected $description = 'Hapus kunci idempotensi yang sudah kedaluwarsa';

    public function handle(LayananIdempotensi $layanan): int
    {
        $jumlah = $layanan->bersihkanKedaluwarsa();
        $this->info("Kunci idempotensi kedaluwarsa dihapus: {$jumlah}.");

        return self::SUCCESS;
    }
}
