<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Core\Peristiwa\LayananKotakKeluar;
use App\Domain\IntegrasiAudit\Application\Services\LayananPanggilanBalikWeb;
use Illuminate\Console\Command;

/** Worker kotak keluar: mengambil peristiwa yang menunggu. */
final class ProsesKotakKeluarPeristiwa extends Command
{
    protected $signature = 'outbox:proses {--batas=50 : Jumlah peristiwa maksimum per jalan}';

    protected $description = 'Publikasikan peristiwa kotak keluar ke panggilan balik web yang berlangganan';

    public function handle(LayananKotakKeluar $kotakKeluar, LayananPanggilanBalikWeb $webhook): int
    {
        $peristiwa = $kotakKeluar->ambilUntukDiproses((int) $this->option('batas'));
        $selesai = 0;
        $gagal = 0;
        $pengiriman = 0;

        foreach ($peristiwa as $satuPeristiwa) {
            try {
                $pengiriman += count($webhook->terbitkan($satuPeristiwa));
                $kotakKeluar->tandaiSelesai($satuPeristiwa);
                $selesai++;
            } catch (\Throwable $e) {
                $kotakKeluar->tandaiGagal($satuPeristiwa, $e->getMessage());
                $gagal++;
            }
        }

        $this->info("Kotak keluar diproses. Selesai: {$selesai}, gagal: {$gagal}, pengiriman dibuat: {$pengiriman}.");

        return self::SUCCESS;
    }
}
