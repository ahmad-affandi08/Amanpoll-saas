<?php

declare(strict_types=1);

namespace App\Domain\Aset\Application\Actions;

use App\Domain\Aset\Infrastructure\Persistence\Models\MeterAset;

final class UbahMeterAset
{
    /**
     * @param array<string, mixed> $data
     */
    public function jalankan(MeterAset $meterAset, array $data): MeterAset
    {
        $meterAset->fill($data);
        $meterAset->save();

        return $meterAset;
    }
}
