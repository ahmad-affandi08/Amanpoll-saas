<?php

declare(strict_types=1);

namespace App\Domain\Persetujuan\Application\Actions;

use App\Domain\Persetujuan\Infrastructure\Persistence\Models\AlurPersetujuan;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\TahapPersetujuan;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

final class HapusTahapPersetujuan
{
    public function jalankan(TahapPersetujuan $tahapPersetujuan): void
    {
        /** @var AlurPersetujuan $alurPersetujuan */
        $alurPersetujuan = $tahapPersetujuan->alurPersetujuan;

        if ($alurPersetujuan->Aktif) {
            throw new AturanBisnisDilanggar('Nonaktifkan alur persetujuan terlebih dahulu sebelum mengubah tahapnya.');
        }

        $tahapPersetujuan->delete();
    }
}
