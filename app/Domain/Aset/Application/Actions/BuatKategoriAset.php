<?php

declare(strict_types=1);

namespace App\Domain\Aset\Application\Actions;

use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;

final class BuatKategoriAset
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function jalankan(array $data): KategoriAset
    {
        return KategoriAset::create($data);
    }
}
