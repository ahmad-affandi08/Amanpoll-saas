<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Application\Actions;

use App\Domain\Persediaan\Infrastructure\Persistence\Models\SukuCadang;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Support\Facades\DB;

final class HapusSukuCadang
{
    public function jalankan(SukuCadang $sukuCadang): void
    {
        if (DB::table('StokSukuCadang')->where('SukuCadangId', $sukuCadang->Id)->exists()) {
            throw new AturanBisnisDilanggar('Suku cadang masih memiliki saldo stok dan tidak dapat dihapus.');
        }

        if (DB::table('DetailMutasiStok')->where('SukuCadangId', $sukuCadang->Id)->exists()) {
            throw new AturanBisnisDilanggar('Suku cadang masih memiliki riwayat mutasi stok dan tidak dapat dihapus.');
        }

        $sukuCadang->delete();
    }
}
