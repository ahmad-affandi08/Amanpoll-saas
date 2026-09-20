<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Application\Actions;

use App\Domain\Persediaan\Infrastructure\Persistence\Models\KompatibilitasSukuCadang;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use App\Shared\Domain\Exceptions\KonflikData;

final class BuatKompatibilitasSukuCadang
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function jalankan(array $data): KompatibilitasSukuCadang
    {
        if (($data['KategoriAsetId'] ?? null) === null && ($data['ModelAsetId'] ?? null) === null && ($data['AsetId'] ?? null) === null) {
            throw new AturanBisnisDilanggar('Kompatibilitas harus menunjuk minimal satu kategori aset, model aset, atau aset spesifik.');
        }

        $duplikat = KompatibilitasSukuCadang::query()
            ->where('SukuCadangId', $data['SukuCadangId'])
            ->where('KategoriAsetId', $data['KategoriAsetId'] ?? null)
            ->where('ModelAsetId', $data['ModelAsetId'] ?? null)
            ->where('AsetId', $data['AsetId'] ?? null)
            ->exists();

        if ($duplikat) {
            throw new KonflikData('Kompatibilitas ini sudah terdaftar untuk suku cadang tersebut.');
        }

        return KompatibilitasSukuCadang::create($data);
    }
}
