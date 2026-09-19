<?php

declare(strict_types=1);

namespace App\Domain\Aset\Application\Actions;

use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Shared\Domain\Services\PemeriksaHierarkiSirkular;

final class UbahKategoriAset
{
    /**
     * @param array<string, mixed> $data
     */
    public function jalankan(KategoriAset $kategoriAset, array $data): KategoriAset
    {
        $indukIdBaru = array_key_exists('IndukId', $data) ? $data['IndukId'] : $kategoriAset->IndukId;

        PemeriksaHierarkiSirkular::pastikanTidakSirkular('KategoriAset', 'IndukId', $kategoriAset->Id, $indukIdBaru);

        $kategoriAset->fill($data);
        $kategoriAset->save();

        return $kategoriAset;
    }
}
