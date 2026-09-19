<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Application\Actions;

use App\Domain\Persediaan\Infrastructure\Persistence\Models\KelompokSukuCadang;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Support\Facades\DB;

final class HapusKelompokSukuCadang
{
    public function jalankan(KelompokSukuCadang $kelompokSukuCadang): void
    {
        if (DB::table('StokSukuCadang')->where('KelompokSukuCadangId', $kelompokSukuCadang->Id)->exists()) {
            throw new AturanBisnisDilanggar('Kelompok/batch ini masih memiliki saldo stok dan tidak dapat dihapus.');
        }

        if (DB::table('DetailMutasiStok')->where('KelompokSukuCadangId', $kelompokSukuCadang->Id)->exists()) {
            throw new AturanBisnisDilanggar('Kelompok/batch ini masih memiliki riwayat mutasi stok dan tidak dapat dihapus.');
        }

        $kelompokSukuCadang->delete();
    }
}
