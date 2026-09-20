<?php

declare(strict_types=1);

namespace App\Domain\Aset\Application\Actions;

use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\MeterAset;

final class BuatMeterAset
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function jalankan(Aset $aset, array $data): MeterAset
    {
        $data['AsetId'] = $aset->Id;

        return MeterAset::create($data);
    }
}
