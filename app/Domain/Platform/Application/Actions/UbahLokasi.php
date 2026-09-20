<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Actions;

use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Shared\Domain\Services\PemeriksaHierarkiSirkular;

final class UbahLokasi
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function jalankan(Lokasi $lokasi, array $data): Lokasi
    {
        $indukIdBaru = array_key_exists('IndukId', $data) ? $data['IndukId'] : $lokasi->IndukId;

        PemeriksaHierarkiSirkular::pastikanTidakSirkular('Lokasi', 'IndukId', $lokasi->Id, $indukIdBaru);

        $lokasi->fill($data);
        $lokasi->save();

        return $lokasi;
    }
}
