<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Application\Actions;

use App\Domain\Persediaan\Infrastructure\Persistence\Models\KategoriSukuCadang;

final class BuatKategoriSukuCadang
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function jalankan(array $data): KategoriSukuCadang
    {
        return KategoriSukuCadang::create($data);
    }
}
