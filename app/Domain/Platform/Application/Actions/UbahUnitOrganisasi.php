<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Actions;

use App\Domain\Platform\Application\Services\PemakaianUnitPengelola;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use App\Shared\Domain\Services\PemeriksaHierarkiSirkular;

final class UbahUnitOrganisasi
{
    public function __construct(private readonly PemakaianUnitPengelola $pemakaian) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function jalankan(UnitOrganisasi $unit, array $data): UnitOrganisasi
    {
        $indukIdBaru = array_key_exists('IndukId', $data) ? $data['IndukId'] : $unit->IndukId;

        PemeriksaHierarkiSirkular::pastikanTidakSirkular('UnitOrganisasi', 'IndukId', $unit->Id, $indukIdBaru);

        // Penjaga terakhir: FormRequest sudah menolaknya di isian, tetapi pemanggil
        // lain (impor, konsol) tidak boleh mencabut tanda yang masih dipakai.
        if ($unit->MengelolaAset && array_key_exists('MengelolaAset', $data) && ! $data['MengelolaAset']) {
            $alasan = $this->pemakaian->alasanTolakCabut($unit->Id);

            if ($alasan !== null) {
                throw new AturanBisnisDilanggar($alasan);
            }
        }

        $unit->fill($data);
        $unit->save();

        return $unit;
    }
}
