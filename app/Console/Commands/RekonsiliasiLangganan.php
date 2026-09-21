<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Langganan\Application\Services\LayananRekonsiliasiLangganan;
use Illuminate\Console\Command;

/**
 * Rekonsiliasi tagihan terhadap pembayarannya (22.06).
 *
 * Selisih dilaporkan sebagai kegagalan perintah supaya penjadwal menandainya,
 * bukan diam-diam dicatat di log yang tidak dibaca siapa pun.
 */
final class RekonsiliasiLangganan extends Command
{
    protected $signature = 'langganan:rekonsiliasi';

    protected $description = 'Mencocokkan status tagihan langganan dengan pembayaran yang tercatat.';

    public function handle(LayananRekonsiliasiLangganan $layanan): int
    {
        $hasil = $layanan->jalankan();

        $this->info("Tagihan diperiksa: {$hasil['Diperiksa']}.");
        $this->info('Status diperbaiki: '.count($hasil['Diperbaiki']).'.');

        foreach ($hasil['Diperbaiki'] as $baris) {
            $this->line("  {$baris['Nomor']}: {$baris['StatusSebelum']} → {$baris['StatusSesudah']}");
        }

        if ($hasil['Selisih'] === []) {
            return self::SUCCESS;
        }

        $this->error('Selisih yang perlu diperiksa manusia: '.count($hasil['Selisih']).'.');
        foreach ($hasil['Selisih'] as $baris) {
            $this->line("  {$baris['Nomor']}: {$baris['Masalah']} (total {$baris['Total']}, dibayar {$baris['Dibayar']})");
        }

        return self::FAILURE;
    }
}
