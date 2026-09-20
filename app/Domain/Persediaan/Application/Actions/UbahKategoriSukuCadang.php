<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Application\Actions;

use App\Domain\Persediaan\Infrastructure\Persistence\Models\KategoriSukuCadang;
use App\Shared\Domain\Services\PemeriksaHierarkiSirkular;

final class UbahKategoriSukuCadang
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function jalankan(KategoriSukuCadang $kategoriSukuCadang, array $data): KategoriSukuCadang
    {
        $indukIdBaru = array_key_exists('IndukId', $data) ? $data['IndukId'] : $kategoriSukuCadang->IndukId;

        PemeriksaHierarkiSirkular::pastikanTidakSirkular('KategoriSukuCadang', 'IndukId', $kategoriSukuCadang->Id, $indukIdBaru);

        $kategoriSukuCadang->fill($data);
        $kategoriSukuCadang->save();

        return $kategoriSukuCadang;
    }
}
