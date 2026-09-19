<?php

declare(strict_types=1);

namespace App\Domain\Persetujuan\Application\Actions;

use App\Domain\Persetujuan\Infrastructure\Persistence\Models\PermintaanPersetujuan;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

final class BatalkanPermintaanPersetujuan
{
    public function jalankan(PermintaanPersetujuan $permintaanPersetujuan): PermintaanPersetujuan
    {
        if ($permintaanPersetujuan->Status !== PermintaanPersetujuan::STATUS_MENUNGGU) {
            throw new AturanBisnisDilanggar('Hanya permintaan yang masih menunggu yang dapat dibatalkan.');
        }

        $permintaanPersetujuan->Status = PermintaanPersetujuan::STATUS_DIBATALKAN;
        $permintaanPersetujuan->SelesaiPada = now()->toImmutable();
        $permintaanPersetujuan->save();

        return $permintaanPersetujuan;
    }
}
