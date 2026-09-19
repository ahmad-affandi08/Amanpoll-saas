<?php

declare(strict_types=1);

namespace App\Domain\Aset\Application\Actions;

use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Support\Facades\DB;

final class HapusKategoriAset
{
    public function jalankan(KategoriAset $kategoriAset): void
    {
        if (DB::table('KategoriAset')->where('IndukId', $kategoriAset->Id)->whereNull('DihapusPada')->exists()) {
            throw new AturanBisnisDilanggar('Kategori aset masih punya sub-kategori dan tidak dapat dihapus.');
        }

        if (DB::table('ModelAset')->where('KategoriAsetId', $kategoriAset->Id)->whereNull('DihapusPada')->exists()) {
            throw new AturanBisnisDilanggar('Kategori aset masih dipakai oleh model aset dan tidak dapat dihapus.');
        }

        if (DB::table('Aset')->where('KategoriAsetId', $kategoriAset->Id)->whereNull('DihapusPada')->exists()) {
            throw new AturanBisnisDilanggar('Kategori aset masih dipakai oleh aset dan tidak dapat dihapus.');
        }

        $kategoriAset->delete();
    }
}
