<?php

declare(strict_types=1);

namespace App\Domain\Persetujuan\Application\Actions;

use App\Domain\Persetujuan\Infrastructure\Persistence\Models\AlurPersetujuan;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\PermintaanPersetujuan;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

final class HapusAlurPersetujuan
{
    public function jalankan(AlurPersetujuan $alurPersetujuan): void
    {
        if (PermintaanPersetujuan::query()->where('AlurPersetujuanId', $alurPersetujuan->Id)->exists()) {
            throw new AturanBisnisDilanggar('Alur persetujuan sudah pernah dipakai dan tidak dapat dihapus.');
        }

        $alurPersetujuan->tahapPersetujuan()->delete();
        $alurPersetujuan->delete();
    }
}
