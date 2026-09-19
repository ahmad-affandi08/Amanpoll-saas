<?php

declare(strict_types=1);

namespace App\Domain\Aset\Http\Resources;

use App\Domain\Aset\Infrastructure\Persistence\Models\ModelAset;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ModelAsetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var ModelAset $modelAset */
        $modelAset = $this->resource;

        return [
            'Id' => $modelAset->Id,
            'KategoriAsetId' => $modelAset->KategoriAsetId,
            'NamaKategoriAset' => $this->whenLoaded('kategoriAset', fn () => $modelAset->kategoriAset?->Nama),
            'MerekId' => $modelAset->MerekId,
            'NamaMerek' => $this->whenLoaded('merek', fn () => $modelAset->merek?->Nama),
            'KodeModel' => $modelAset->KodeModel,
            'Nama' => $modelAset->Nama,
            'Produsen' => $modelAset->Produsen,
            'Spesifikasi' => $modelAset->Spesifikasi,
            'IntervalPemeliharaanHari' => $modelAset->IntervalPemeliharaanHari,
            'IntervalKalibrasiHari' => $modelAset->IntervalKalibrasiHari,
            'UmurManfaatBulan' => $modelAset->UmurManfaatBulan,
            'DibuatPada' => $modelAset->DibuatPada->toIso8601String(),
        ];
    }
}
