<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Actions;

use App\Domain\Platform\Application\Services\PemakaianUnitPengelola;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Support\Facades\DB;

final class HapusUnitOrganisasi
{
    public function __construct(private readonly PemakaianUnitPengelola $pemakaian) {}

    public function jalankan(UnitOrganisasi $unit): void
    {
        if (DB::table('UnitOrganisasi')->where('IndukId', $unit->Id)->whereNull('DihapusPada')->exists()) {
            throw new AturanBisnisDilanggar('Unit masih punya sub-unit dan tidak dapat dihapus.');
        }

        // Menghapus sama dengan mencabut tanda Mengelola Aset, jadi syaratnya sama.
        $alasan = $this->pemakaian->alasanTolakHapus($unit->Id);

        if ($alasan !== null) {
            throw new AturanBisnisDilanggar($alasan);
        }

        $unit->delete();
    }
}
