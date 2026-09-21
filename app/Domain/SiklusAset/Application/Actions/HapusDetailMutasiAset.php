<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Application\Actions;

use App\Domain\SiklusAset\Domain\Enums\StatusPermintaanMutasiAset;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\DetailMutasiAset;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\PermintaanMutasiAset;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

final class HapusDetailMutasiAset
{
    public function jalankan(PermintaanMutasiAset $permintaan, DetailMutasiAset $detail): void
    {
        if ($permintaan->Status !== StatusPermintaanMutasiAset::Draft->value) {
            throw new AturanBisnisDilanggar('Detail aset hanya boleh dihapus selagi permintaan masih berupa draft.');
        }

        $detail->delete();
    }
}
