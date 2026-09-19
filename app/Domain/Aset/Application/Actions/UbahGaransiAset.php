<?php

declare(strict_types=1);

namespace App\Domain\Aset\Application\Actions;

use App\Domain\Aset\Infrastructure\Persistence\Models\GaransiAset;

final class UbahGaransiAset
{
    /**
     * @param array<string, mixed> $data
     */
    public function jalankan(GaransiAset $garansiAset, array $data): GaransiAset
    {
        $garansiAset->fill($data);
        $garansiAset->save();

        return $garansiAset;
    }
}
