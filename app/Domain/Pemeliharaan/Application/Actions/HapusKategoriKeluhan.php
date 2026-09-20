<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KategoriKeluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

final class HapusKategoriKeluhan
{
    public function __construct(private readonly LayananAudit $audit) {}

    public function jalankan(KategoriKeluhan $kategori): void
    {
        if ($kategori->anak()->exists() || Keluhan::query()->where('KategoriKeluhanId', $kategori->Id)->exists()) {
            throw new AturanBisnisDilanggar('Kategori masih memiliki subkategori atau dipakai keluhan. Nonaktifkan sebagai pengganti penghapusan.');
        }

        $sebelum = $kategori->toArray();
        $kategori->delete();
        $this->audit->catat('Hapus', 'KategoriKeluhan', $kategori->Id, $sebelum);
    }
}
