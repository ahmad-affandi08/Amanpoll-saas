<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Application\Actions;

use App\Domain\SiklusAset\Domain\Enums\StatusDetailMutasiAset;
use App\Domain\SiklusAset\Domain\Enums\StatusPermintaanMutasiAset;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\DetailMutasiAset;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\PermintaanMutasiAset;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use App\Shared\Domain\Exceptions\KonflikData;

final class TambahDetailMutasiAset
{
    public function jalankan(PermintaanMutasiAset $permintaan, string $asetId, ?string $catatan): DetailMutasiAset
    {
        if ($permintaan->Status !== StatusPermintaanMutasiAset::Draft->value) {
            throw new AturanBisnisDilanggar('Detail aset hanya boleh ditambahkan selagi permintaan masih berupa draft.');
        }

        if (DetailMutasiAset::query()->where('PermintaanMutasiAsetId', $permintaan->Id)->where('AsetId', $asetId)->exists()) {
            throw new KonflikData('Aset ini sudah ada dalam daftar mutasi.');
        }

        return DetailMutasiAset::create([
            'OrganisasiId' => $permintaan->OrganisasiId,
            'PermintaanMutasiAsetId' => $permintaan->Id,
            'AsetId' => $asetId,
            'Status' => StatusDetailMutasiAset::Menunggu->value,
            'Catatan' => $catatan,
        ]);
    }
}
