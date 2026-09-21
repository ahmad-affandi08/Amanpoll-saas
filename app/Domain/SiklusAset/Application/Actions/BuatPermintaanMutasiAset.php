<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Application\Actions;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Platform\Application\Services\LayananNomorDokumen;
use App\Domain\SiklusAset\Domain\Enums\StatusPermintaanMutasiAset;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\PermintaanMutasiAset;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

final class BuatPermintaanMutasiAset
{
    private const JENIS_DOKUMEN = 'PermintaanMutasiAset';

    public function __construct(
        private readonly LayananNomorDokumen $layananNomorDokumen,
        private readonly KonteksOrganisasi $konteksOrganisasi,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function jalankan(array $data, string $dimintaOleh): PermintaanMutasiAset
    {
        if (($data['LokasiTujuanId'] ?? null) === null && ($data['UnitTujuanId'] ?? null) === null) {
            throw new AturanBisnisDilanggar('Mutasi harus memiliki lokasi tujuan atau unit tujuan.');
        }

        $data['Nomor'] = $this->layananNomorDokumen->berikutnya($this->konteksOrganisasi->wajibId(), self::JENIS_DOKUMEN);
        $data['DimintaOleh'] = $dimintaOleh;
        $data['DimintaPada'] = now();
        $data['Status'] = StatusPermintaanMutasiAset::Draft->value;

        /** @var PermintaanMutasiAset $permintaan */
        $permintaan = PermintaanMutasiAset::create($data);

        return $permintaan;
    }
}
