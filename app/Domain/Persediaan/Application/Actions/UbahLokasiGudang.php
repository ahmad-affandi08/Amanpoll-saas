<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Application\Actions;

use App\Domain\Persediaan\Infrastructure\Persistence\Models\LokasiGudang;
use App\Shared\Domain\Services\PemeriksaHierarkiSirkular;

final class UbahLokasiGudang
{
    /**
     * @param array<string, mixed> $data
     */
    public function jalankan(LokasiGudang $lokasiGudang, array $data): LokasiGudang
    {
        $indukIdBaru = array_key_exists('IndukId', $data) ? $data['IndukId'] : $lokasiGudang->IndukId;

        PemeriksaHierarkiSirkular::pastikanTidakSirkular('LokasiGudang', 'IndukId', $lokasiGudang->Id, $indukIdBaru);

        $lokasiGudang->fill($data);
        $lokasiGudang->save();

        return $lokasiGudang;
    }
}
