<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Pemasaran\Domain\Enums\StatusPengirimanWhatsApp;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\PengirimanWhatsAppPemasaran;
use App\Domain\Pemasaran\Jobs\KirimWhatsAppPemasaran;
use Illuminate\Console\Command;

/** Mengantrekan pesan WhatsApp yang jadwalnya sudah tiba; frequency cap ditegakkan pengirimnya (MARKETING.md 16). */
final class KirimAntrianWhatsAppPemasaran extends Command
{
    protected $signature = 'pemasaran:kirim-antrian-whatsapp {--batas=200 : Banyak pesan yang diantrekan sekali jalan}';

    protected $description = 'Antrekan pesan WhatsApp pemasaran yang sudah jatuh tempo';

    public function handle(): int
    {
        $batas = max((int) $this->option('batas'), 1);

        $jatuhTempo = PengirimanWhatsAppPemasaran::query()
            ->where('Status', StatusPengirimanWhatsApp::Terjadwal->value)
            ->where('JadwalPada', '<=', now())
            ->orderBy('JadwalPada')
            ->limit($batas)
            ->pluck('Id');

        foreach ($jatuhTempo as $id) {
            KirimWhatsAppPemasaran::dispatch((string) $id);
        }

        $this->info("Mengantrekan {$jatuhTempo->count()} pesan WhatsApp.");

        return self::SUCCESS;
    }
}
