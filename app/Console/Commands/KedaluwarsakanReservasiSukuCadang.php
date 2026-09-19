<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Core\Organisasi\ScopeOrganisasi;
use App\Domain\Persediaan\Application\Services\LayananSaldoReservasi;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\ReservasiSukuCadang;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use Illuminate\Console\Command;

/**
 * Melepas hold JumlahDitahan milik reservasi yang KadaluarsaPada-nya sudah
 * lewat tapi belum dilepas/dipakai (10.06 "Expiry"). Operasi sistem lintas
 * organisasi terjadwal, jadi scope organisasi sengaja dilewati (pola sama
 * dengan catatan-akses:bersihkan).
 */
final class KedaluwarsakanReservasiSukuCadang extends Command
{
    protected $signature = 'reservasi-suku-cadang:kedaluwarsakan';

    protected $description = 'Kedaluwarsakan reservasi suku cadang yang melewati KadaluarsaPada dan lepas hold-nya';

    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananSaldoReservasi $layananSaldoReservasi,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $reservasiKedaluwarsa = ReservasiSukuCadang::withoutGlobalScope(ScopeOrganisasi::class)
            ->where('Status', ReservasiSukuCadang::STATUS_AKTIF)
            ->whereNotNull('KadaluarsaPada')
            ->where('KadaluarsaPada', '<', now())
            ->get();

        foreach ($reservasiKedaluwarsa as $reservasi) {
            $this->transaksi->jalankan(function () use ($reservasi): void {
                $this->layananSaldoReservasi->ubahDitahan(
                    $reservasi->OrganisasiId,
                    $reservasi->GudangId,
                    $reservasi->SukuCadangId,
                    -1 * (float) $reservasi->Jumlah,
                );

                $reservasi->Status = ReservasiSukuCadang::STATUS_KADALUARSA;
                $reservasi->save();
            });
        }

        $this->info("Mengkedaluwarsakan {$reservasiKedaluwarsa->count()} reservasi suku cadang.");

        return self::SUCCESS;
    }
}
