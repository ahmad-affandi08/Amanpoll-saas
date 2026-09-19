<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \App\Domain\SiklusAset\Infrastructure\Persistence\Models\DetailPenghapusanAset $resource
 */
final class DetailPenghapusanAsetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $detail = $this->resource;

        return [
            'Id' => $detail->Id,
            'AsetId' => $detail->AsetId,
            'NamaAset' => $this->whenLoaded('aset', fn () => $detail->aset?->Nama),
            'KodeAset' => $this->whenLoaded('aset', fn () => $detail->aset?->KodeAset),
            'NilaiBukuSaatPenghapusan' => $detail->NilaiBukuSaatPenghapusan,
            'HasilPelepasan' => $detail->HasilPelepasan,
            'Status' => $detail->Status,
            'Catatan' => $detail->Catatan,
            'DibuatPada' => $detail->DibuatPada,
        ];
    }
}
