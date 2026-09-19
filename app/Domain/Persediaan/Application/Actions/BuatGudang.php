<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Application\Actions;

use App\Domain\Persediaan\Infrastructure\Persistence\Models\Gudang;

final class BuatGudang
{
    /**
     * @param array<string, mixed> $data
     */
    public function jalankan(array $data): Gudang
    {
        return Gudang::create($data);
    }
}
