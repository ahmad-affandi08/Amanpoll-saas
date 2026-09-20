<?php

declare(strict_types=1);

namespace App\Domain\Penyedia\Application\Actions;

use App\Domain\Penyedia\Infrastructure\Persistence\Models\KategoriPenyedia;

final class BuatKategoriPenyedia
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function jalankan(array $data): KategoriPenyedia
    {
        return KategoriPenyedia::create($data);
    }
}
