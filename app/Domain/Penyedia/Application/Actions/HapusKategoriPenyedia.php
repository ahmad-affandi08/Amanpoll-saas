<?php

declare(strict_types=1);

namespace App\Domain\Penyedia\Application\Actions;

use App\Domain\Penyedia\Infrastructure\Persistence\Models\KategoriPenyedia;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Support\Facades\DB;

final class HapusKategoriPenyedia
{
    public function jalankan(KategoriPenyedia $kategoriPenyedia): void
    {
        if (DB::table('PenyediaKategori')->where('KategoriPenyediaId', $kategoriPenyedia->Id)->exists()) {
            throw new AturanBisnisDilanggar('Kategori penyedia masih dipakai oleh penyedia dan tidak dapat dihapus.');
        }

        $kategoriPenyedia->delete();
    }
}
