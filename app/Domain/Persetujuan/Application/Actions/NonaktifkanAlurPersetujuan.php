<?php

declare(strict_types=1);

namespace App\Domain\Persetujuan\Application\Actions;

use App\Domain\Persetujuan\Infrastructure\Persistence\Models\AlurPersetujuan;

final class NonaktifkanAlurPersetujuan
{
    public function jalankan(AlurPersetujuan $alurPersetujuan): AlurPersetujuan
    {
        $alurPersetujuan->Aktif = false;
        $alurPersetujuan->save();

        return $alurPersetujuan;
    }
}
