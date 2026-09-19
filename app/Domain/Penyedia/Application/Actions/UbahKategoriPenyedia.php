<?php

declare(strict_types=1);

namespace App\Domain\Penyedia\Application\Actions;

use App\Domain\Penyedia\Infrastructure\Persistence\Models\KategoriPenyedia;

final class UbahKategoriPenyedia
{
    /**
     * @param array<string, mixed> $data
     */
    public function jalankan(KategoriPenyedia $kategoriPenyedia, array $data): KategoriPenyedia
    {
        $kategoriPenyedia->fill($data);
        $kategoriPenyedia->save();

        return $kategoriPenyedia;
    }
}
