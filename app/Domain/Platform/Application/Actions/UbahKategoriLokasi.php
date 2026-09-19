<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Actions;

use App\Domain\Platform\Infrastructure\Persistence\Models\KategoriLokasi;

final class UbahKategoriLokasi
{
    /**
     * @param array<string, mixed> $data
     */
    public function jalankan(KategoriLokasi $kategoriLokasi, array $data): KategoriLokasi
    {
        $kategoriLokasi->fill($data);
        $kategoriLokasi->save();

        return $kategoriLokasi;
    }
}
