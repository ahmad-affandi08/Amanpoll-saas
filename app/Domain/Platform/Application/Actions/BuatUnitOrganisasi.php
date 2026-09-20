<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Actions;

use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;

final class BuatUnitOrganisasi
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function jalankan(array $data): UnitOrganisasi
    {
        return UnitOrganisasi::create($data);
    }
}
