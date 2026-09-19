<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Application\Actions;

use App\Domain\Persediaan\Infrastructure\Persistence\Models\DetailMutasiStok;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\MutasiStok;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

final class HapusDetailMutasiStok
{
    public function jalankan(MutasiStok $mutasiStok, DetailMutasiStok $detailMutasiStok): void
    {
        if ($mutasiStok->Status !== MutasiStok::STATUS_DRAFT) {
            throw new AturanBisnisDilanggar('Hanya mutasi berstatus draft yang bisa dihapus detailnya.');
        }

        $detailMutasiStok->delete();
    }
}
