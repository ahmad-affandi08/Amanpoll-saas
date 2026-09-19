<?php

declare(strict_types=1);

namespace App\Domain\Persetujuan\Application\Actions;

use App\Domain\Persetujuan\Infrastructure\Persistence\Models\AlurPersetujuan;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

final class AktifkanAlurPersetujuan
{
    public function jalankan(AlurPersetujuan $alurPersetujuan): AlurPersetujuan
    {
        if ($alurPersetujuan->tahapPersetujuan()->count() === 0) {
            throw new AturanBisnisDilanggar('Alur persetujuan tidak dapat diaktifkan tanpa tahap.');
        }

        $alurPersetujuan->Aktif = true;
        $alurPersetujuan->save();

        return $alurPersetujuan;
    }
}
