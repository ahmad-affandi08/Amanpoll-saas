<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Application\Actions;

use App\Domain\Persediaan\Infrastructure\Persistence\Models\Gudang;

final class UbahGudang
{
    /**
     * @param array<string, mixed> $data
     */
    public function jalankan(Gudang $gudang, array $data): Gudang
    {
        $gudang->fill($data);
        $gudang->save();

        return $gudang;
    }
}
