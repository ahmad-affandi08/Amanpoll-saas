<?php

declare(strict_types=1);

namespace App\Domain\Persetujuan\Application\Actions;

use App\Domain\Persetujuan\Infrastructure\Persistence\Models\AlurPersetujuan;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\TahapPersetujuan;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

final class UbahTahapPersetujuan
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function jalankan(TahapPersetujuan $tahapPersetujuan, array $data): TahapPersetujuan
    {
        /** @var AlurPersetujuan $alurPersetujuan */
        $alurPersetujuan = $tahapPersetujuan->alurPersetujuan;

        if ($alurPersetujuan->Aktif) {
            throw new AturanBisnisDilanggar('Nonaktifkan alur persetujuan terlebih dahulu sebelum mengubah tahapnya.');
        }

        $tahapPersetujuan->fill($data);
        $tahapPersetujuan->save();

        return $tahapPersetujuan;
    }
}
