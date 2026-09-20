<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Actions;

use App\Domain\Platform\Infrastructure\Persistence\Models\KategoriLokasi;

final class BuatKategoriLokasi
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function jalankan(array $data): KategoriLokasi
    {
        return KategoriLokasi::create($data);
    }
}
