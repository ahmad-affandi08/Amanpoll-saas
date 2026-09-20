<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Actions;

use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;

final class BuatLokasi
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function jalankan(array $data): Lokasi
    {
        return Lokasi::create($data);
    }
}
