<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Application\Actions;

use App\Domain\Persediaan\Infrastructure\Persistence\Models\KategoriSukuCadang;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Support\Facades\DB;

final class HapusKategoriSukuCadang
{
    public function jalankan(KategoriSukuCadang $kategoriSukuCadang): void
    {
        if (DB::table('KategoriSukuCadang')->where('IndukId', $kategoriSukuCadang->Id)->exists()) {
            throw new AturanBisnisDilanggar('Kategori suku cadang masih punya sub-kategori dan tidak dapat dihapus.');
        }

        if (DB::table('SukuCadang')->where('KategoriSukuCadangId', $kategoriSukuCadang->Id)->whereNull('DihapusPada')->exists()) {
            throw new AturanBisnisDilanggar('Kategori suku cadang masih dipakai oleh suku cadang dan tidak dapat dihapus.');
        }

        $kategoriSukuCadang->delete();
    }
}
