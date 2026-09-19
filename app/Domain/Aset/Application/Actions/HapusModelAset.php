<?php

declare(strict_types=1);

namespace App\Domain\Aset\Application\Actions;

use App\Domain\Aset\Infrastructure\Persistence\Models\ModelAset;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Support\Facades\DB;

final class HapusModelAset
{
    public function jalankan(ModelAset $modelAset): void
    {
        if (DB::table('Aset')->where('ModelAsetId', $modelAset->Id)->whereNull('DihapusPada')->exists()) {
            throw new AturanBisnisDilanggar('Model aset masih dipakai oleh aset dan tidak dapat dihapus.');
        }

        $modelAset->delete();
    }
}
