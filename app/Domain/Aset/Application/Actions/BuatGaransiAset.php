<?php

declare(strict_types=1);

namespace App\Domain\Aset\Application\Actions;

use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\GaransiAset;

final class BuatGaransiAset
{
    /**
     * @param array<string, mixed> $data
     */
    public function jalankan(Aset $aset, array $data): GaransiAset
    {
        $data['AsetId'] = $aset->Id;

        return GaransiAset::create($data);
    }
}
