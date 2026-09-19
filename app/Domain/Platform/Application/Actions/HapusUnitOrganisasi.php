<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Actions;

use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Support\Facades\DB;

final class HapusUnitOrganisasi
{
    public function jalankan(UnitOrganisasi $unit): void
    {
        if (DB::table('UnitOrganisasi')->where('IndukId', $unit->Id)->whereNull('DihapusPada')->exists()) {
            throw new AturanBisnisDilanggar('Unit masih punya sub-unit dan tidak dapat dihapus.');
        }

        $unit->delete();
    }
}
