<?php

declare(strict_types=1);

namespace App\Domain\Aset\Application\Actions;

use App\Domain\Aset\Infrastructure\Persistence\Models\MeterAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\PembacaanMeterAset;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

final class CatatPembacaanMeter
{
    /**
     * @param array<string, mixed> $data
     */
    public function jalankan(MeterAset $meterAset, array $data, ?string $dicatatOleh): PembacaanMeterAset
    {
        if ($meterAset->Jenis === MeterAset::JENIS_KUMULATIF) {
            $pembacaanTerakhir = $meterAset->pembacaan()->orderByDesc('DibacaPada')->first();
            $nilaiTerakhir = $pembacaanTerakhir === null ? $meterAset->NilaiAwal : $pembacaanTerakhir->Nilai;

            if ((float) $data['Nilai'] < (float) $nilaiTerakhir) {
                throw new AturanBisnisDilanggar('Pembacaan meter kumulatif tidak boleh lebih kecil dari pembacaan sebelumnya.');
            }
        }

        $data['MeterAsetId'] = $meterAset->Id;
        $data['DicatatOleh'] = $dicatatOleh;

        return PembacaanMeterAset::create($data);
    }
}
