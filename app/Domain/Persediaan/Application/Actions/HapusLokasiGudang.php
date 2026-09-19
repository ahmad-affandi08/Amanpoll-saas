<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Application\Actions;

use App\Domain\Persediaan\Infrastructure\Persistence\Models\LokasiGudang;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Support\Facades\DB;

final class HapusLokasiGudang
{
    public function jalankan(LokasiGudang $lokasiGudang): void
    {
        if (DB::table('LokasiGudang')->where('IndukId', $lokasiGudang->Id)->exists()) {
            throw new AturanBisnisDilanggar('Lokasi gudang masih punya sub-lokasi dan tidak dapat dihapus.');
        }

        if (DB::table('StokSukuCadang')->where('LokasiGudangId', $lokasiGudang->Id)->exists()) {
            throw new AturanBisnisDilanggar('Lokasi gudang masih memiliki saldo stok dan tidak dapat dihapus.');
        }

        $lokasiGudang->delete();
    }
}
