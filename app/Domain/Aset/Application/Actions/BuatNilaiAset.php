<?php

declare(strict_types=1);

namespace App\Domain\Aset\Application\Actions;

use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\NilaiAset;
use App\Shared\Domain\Exceptions\KonflikData;

final class BuatNilaiAset
{
    /**
     * @param array<string, mixed> $data
     */
    public function jalankan(Aset $aset, array $data): NilaiAset
    {
        $sudahAda = $aset->nilaiAset()->whereDate('TanggalNilai', $data['TanggalNilai'])->exists();
        if ($sudahAda) {
            throw new KonflikData('Sudah ada catatan nilai aset untuk tanggal tersebut.');
        }

        $data['AsetId'] = $aset->Id;

        return NilaiAset::create($data);
    }
}
