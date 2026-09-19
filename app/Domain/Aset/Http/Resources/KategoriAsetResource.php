<?php

declare(strict_types=1);

namespace App\Domain\Aset\Http\Resources;

use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class KategoriAsetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var KategoriAset $kategoriAset */
        $kategoriAset = $this->resource;

        return [
            'Id' => $kategoriAset->Id,
            'IndukId' => $kategoriAset->IndukId,
            'NamaInduk' => $this->whenLoaded('induk', fn () => $kategoriAset->induk?->Nama),
            'Kode' => $kategoriAset->Kode,
            'Nama' => $kategoriAset->Nama,
            'UmurManfaatBulan' => $kategoriAset->UmurManfaatBulan,
            'MetodePenyusutanBawaan' => $kategoriAset->MetodePenyusutanBawaan,
            'PersentaseNilaiResidu' => $kategoriAset->PersentaseNilaiResidu,
            'MemerlukanKalibrasi' => $kategoriAset->MemerlukanKalibrasi,
            'MemerlukanPemeliharaan' => $kategoriAset->MemerlukanPemeliharaan,
            'DibuatPada' => $kategoriAset->DibuatPada->toIso8601String(),
        ];
    }
}
