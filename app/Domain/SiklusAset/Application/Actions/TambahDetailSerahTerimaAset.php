<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Application\Actions;

use App\Domain\SiklusAset\Infrastructure\Persistence\Models\DetailSerahTerimaAset;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\SerahTerimaAset;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use App\Shared\Domain\Exceptions\KonflikData;

final class TambahDetailSerahTerimaAset
{
    public function jalankan(SerahTerimaAset $serahTerima, string $asetId, ?string $kondisiSaatDiserahkan, ?string $catatan): DetailSerahTerimaAset
    {
        if ($serahTerima->Status !== SerahTerimaAset::STATUS_DISERAHKAN) {
            throw new AturanBisnisDilanggar('Detail aset hanya boleh ditambahkan sebelum dokumen ini diterima.');
        }

        if (DetailSerahTerimaAset::query()->where('SerahTerimaAsetId', $serahTerima->Id)->where('AsetId', $asetId)->exists()) {
            throw new KonflikData('Aset ini sudah ada dalam dokumen serah terima.');
        }

        return DetailSerahTerimaAset::create([
            'OrganisasiId' => $serahTerima->OrganisasiId,
            'SerahTerimaAsetId' => $serahTerima->Id,
            'AsetId' => $asetId,
            'KondisiSaatDiserahkan' => $kondisiSaatDiserahkan,
            'Catatan' => $catatan,
        ]);
    }
}
