<?php

declare(strict_types=1);

namespace App\Domain\Persetujuan\Application\Actions;

use App\Domain\Persetujuan\Infrastructure\Persistence\Models\AlurPersetujuan;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\TahapPersetujuan;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

final class BuatTahapPersetujuan
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function jalankan(AlurPersetujuan $alurPersetujuan, array $data): TahapPersetujuan
    {
        if ($alurPersetujuan->Aktif) {
            throw new AturanBisnisDilanggar('Nonaktifkan alur persetujuan terlebih dahulu sebelum mengubah tahapnya.');
        }

        $data['AlurPersetujuanId'] = $alurPersetujuan->Id;

        return TahapPersetujuan::create($data);
    }
}
