<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Http\Resources;

use App\Domain\Persediaan\Infrastructure\Persistence\Models\KompatibilitasSukuCadang;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class KompatibilitasSukuCadangResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var KompatibilitasSukuCadang $kompatibilitas */
        $kompatibilitas = $this->resource;

        return [
            'Id' => $kompatibilitas->Id,
            'SukuCadangId' => $kompatibilitas->SukuCadangId,
            'NamaSukuCadang' => $this->whenLoaded('sukuCadang', fn () => $kompatibilitas->sukuCadang?->Nama),
            'KodeSukuCadang' => $this->whenLoaded('sukuCadang', fn () => $kompatibilitas->sukuCadang?->Kode),
            'KategoriAsetId' => $kompatibilitas->KategoriAsetId,
            'NamaKategoriAset' => $this->whenLoaded('kategoriAset', fn () => $kompatibilitas->kategoriAset?->Nama),
            'ModelAsetId' => $kompatibilitas->ModelAsetId,
            'NamaModelAset' => $this->whenLoaded('modelAset', fn () => $kompatibilitas->modelAset?->Nama),
            'AsetId' => $kompatibilitas->AsetId,
            'NamaAset' => $this->whenLoaded('aset', fn () => $kompatibilitas->aset?->Nama),
            'Catatan' => $kompatibilitas->Catatan,
            'DibuatPada' => $kompatibilitas->DibuatPada->toIso8601String(),
        ];
    }
}
