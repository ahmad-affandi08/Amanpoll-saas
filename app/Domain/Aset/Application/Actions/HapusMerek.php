<?php

declare(strict_types=1);

namespace App\Domain\Aset\Application\Actions;

use App\Domain\Aset\Infrastructure\Persistence\Models\Merek;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Support\Facades\DB;

final class HapusMerek
{
    public function jalankan(Merek $merek): void
    {
        if (DB::table('ModelAset')->where('MerekId', $merek->Id)->whereNull('DihapusPada')->exists()) {
            throw new AturanBisnisDilanggar('Merek masih dipakai oleh model aset dan tidak dapat dihapus.');
        }

        $merek->delete();
    }
}
