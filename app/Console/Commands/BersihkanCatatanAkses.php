<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Core\Organisasi\ScopeOrganisasi;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\CatatanAkses;
use Illuminate\Console\Command;

final class BersihkanCatatanAkses extends Command
{
    protected $signature = 'catatan-akses:bersihkan';

    protected $description = 'Hapus CatatanAkses yang lebih tua dari retention policy (config amanpoll.retensi_catatan_akses_hari)';

    public function handle(): int
    {
        $hariRetensi = (int) config('amanpoll.retensi_catatan_akses_hari', 90);
        $batasWaktu = now()->subDays($hariRetensi);

        // Retensi berlaku lintas seluruh organisasi (operasi sistem terjadwal,
        // bukan permintaan tenant), jadi scope organisasi sengaja dilewati.
        $jumlahDihapus = CatatanAkses::withoutGlobalScope(ScopeOrganisasi::class)
            ->where('DibuatPada', '<', $batasWaktu)
            ->delete();

        $this->info("Menghapus {$jumlahDihapus} CatatanAkses lebih tua dari {$hariRetensi} hari.");

        return self::SUCCESS;
    }
}
