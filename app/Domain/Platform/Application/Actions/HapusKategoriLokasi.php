<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Actions;

use App\Domain\Platform\Infrastructure\Persistence\Models\KategoriLokasi;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Support\Facades\DB;

final class HapusKategoriLokasi
{
    public function jalankan(KategoriLokasi $kategoriLokasi): void
    {
        if (DB::table('Lokasi')->where('KategoriLokasiId', $kategoriLokasi->Id)->whereNull('DihapusPada')->exists()) {
            throw new AturanBisnisDilanggar('Kategori lokasi masih dipakai oleh lokasi dan tidak dapat dihapus.');
        }

        $kategoriLokasi->delete();
    }
}
