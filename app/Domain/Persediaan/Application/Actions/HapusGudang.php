<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Application\Actions;

use App\Domain\Persediaan\Infrastructure\Persistence\Models\Gudang;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Support\Facades\DB;

final class HapusGudang
{
    public function jalankan(Gudang $gudang): void
    {
        if (DB::table('LokasiGudang')->where('GudangId', $gudang->Id)->exists()) {
            throw new AturanBisnisDilanggar('Gudang masih punya lokasi gudang dan tidak dapat dihapus.');
        }

        if (DB::table('StokSukuCadang')->where('GudangId', $gudang->Id)->exists()) {
            throw new AturanBisnisDilanggar('Gudang masih memiliki saldo stok dan tidak dapat dihapus.');
        }

        if (DB::table('MutasiStok')->where('GudangAsalId', $gudang->Id)->orWhere('GudangTujuanId', $gudang->Id)->exists()) {
            throw new AturanBisnisDilanggar('Gudang masih memiliki riwayat mutasi stok dan tidak dapat dihapus.');
        }

        $gudang->delete();
    }
}
