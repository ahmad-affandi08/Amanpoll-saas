<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\PreventifInspeksi\Application\Actions\JadwalkanPemeliharaanPreventif;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('pemeliharaan:jadwalkan-preventif {--organisasi= : ID Organisasi tertentu} {--horizon= : Jumlah hari horizon ke depan} {--tanggal= : Tanggal acuan (YYYY-MM-DD)}')]
#[Description('Jadwalkan pemeliharaan preventif secara otomatis berdasarkan interval rencana dan horizon waktu.')]
final class JadwalkanPemeliharaanPreventifCommand extends Command
{
    public function handle(JadwalkanPemeliharaanPreventif $action): int
    {
        $organisasiId = $this->option('organisasi') ? (string) $this->option('organisasi') : null;
        $horizon = $this->option('horizon') !== null ? (int) $this->option('horizon') : null;
        $tanggal = $this->option('tanggal') ? CarbonImmutable::parse((string) $this->option('tanggal')) : CarbonImmutable::now();

        $this->info("Menjalankan penjadwalan preventif (Acuan: {$tanggal->toDateString()})...");

        $hasil = $action->jalankan(
            tanggalAcuan: $tanggal,
            horizonHari: $horizon,
            organisasiId: $organisasiId
        );

        $this->info("Selesai. Jadwal dibuat: {$hasil['jadwalDibuat']}, Perintah Kerja dibuat: {$hasil['perintahKerjaDibuat']}, Dilewati (Idempoten): {$hasil['dilewati']}.");

        return self::SUCCESS;
    }
}
