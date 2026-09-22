<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Actions;

use App\Core\Izin\PemeriksaIzin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Mengganti seluruh set Izin milik satu Peran (dari checkbox di form edit). */
final class SinkronkanIzinPeran
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly PemeriksaIzin $pemeriksaIzin,
    ) {}

    /**
     * @param  list<string>  $izinId
     */
    public function jalankan(Peran $peran, array $izinId): void
    {
        $this->transaksi->jalankan(function () use ($peran, $izinId): void {
            DB::table('PeranIzin')->where('PeranId', $peran->Id)->delete();

            $baris = array_map(
                fn (string $id) => [
                    'Id' => (string) Str::ulid(),
                    'PeranId' => $peran->Id,
                    'IzinId' => $id,
                    'DibuatPada' => now(),
                ],
                array_values(array_unique($izinId)),
            );

            if ($baris !== []) {
                DB::table('PeranIzin')->insert($baris);
            }
        });

        $penggunaTerdampak = DB::table('PenggunaPeran')
            ->where('PeranId', $peran->Id)
            ->pluck('PenggunaId');

        foreach ($penggunaTerdampak as $penggunaId) {
            $this->pemeriksaIzin->bersihkanCache($peran->OrganisasiId, (string) $penggunaId);
        }
    }
}
