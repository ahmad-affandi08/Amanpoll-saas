<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\IntegrasiAudit\Application\Services\LayananPanggilanBalikWeb;
use App\Domain\IntegrasiAudit\Domain\Enums\StatusPengirimanPanggilanBalikWeb;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\PengirimanPanggilanBalikWeb;
use Illuminate\Console\Command;

/** Mengirim ulang panggilan balik web yang gagal dan sudah tiba jadwal percobaannya (19.05). */
final class KirimUlangPanggilanBalikWeb extends Command
{
    protected $signature = 'panggilan-balik:kirim-ulang {--batas=50}';

    protected $description = 'Kirim ulang panggilan balik web yang gagal dan sudah jatuh tempo';

    public function handle(LayananPanggilanBalikWeb $layanan): int
    {
        $daftar = PengirimanPanggilanBalikWeb::query()
            ->withoutGlobalScopes()
            ->where('Status', StatusPengirimanPanggilanBalikWeb::Gagal->value)
            ->whereNotNull('JadwalCobaLagiPada')
            ->where('JadwalCobaLagiPada', '<=', now())
            ->orderBy('JadwalCobaLagiPada')
            ->limit((int) $this->option('batas'))
            ->get();

        $berhasil = 0;
        foreach ($daftar as $pengiriman) {
            if ($layanan->kirim($pengiriman)) {
                $berhasil++;
            }
        }

        $this->info('Pengiriman ulang selesai. Dicoba: '.$daftar->count().", berhasil: {$berhasil}.");

        return self::SUCCESS;
    }
}
