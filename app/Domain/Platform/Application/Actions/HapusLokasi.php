<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Actions;

use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Support\Facades\DB;

final class HapusLokasi
{
    public function jalankan(Lokasi $lokasi): void
    {
        if (DB::table('Lokasi')->where('IndukId', $lokasi->Id)->whereNull('DihapusPada')->exists()) {
            throw new AturanBisnisDilanggar('Lokasi masih punya sub-lokasi dan tidak dapat dihapus.');
        }

        $lokasi->delete();
    }
}
