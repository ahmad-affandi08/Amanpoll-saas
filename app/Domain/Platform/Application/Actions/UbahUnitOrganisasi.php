<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Actions;

use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use App\Shared\Domain\Services\PemeriksaHierarkiSirkular;

final class UbahUnitOrganisasi
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function jalankan(UnitOrganisasi $unit, array $data): UnitOrganisasi
    {
        $indukIdBaru = array_key_exists('IndukId', $data) ? $data['IndukId'] : $unit->IndukId;

        PemeriksaHierarkiSirkular::pastikanTidakSirkular('UnitOrganisasi', 'IndukId', $unit->Id, $indukIdBaru);

        $unit->fill($data);
        $unit->save();

        return $unit;
    }
}
