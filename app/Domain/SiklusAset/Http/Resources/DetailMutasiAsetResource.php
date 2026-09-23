<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Http\Resources;

use App\Domain\SiklusAset\Infrastructure\Persistence\Models\DetailMutasiAset;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property DetailMutasiAset $resource
 */
final class DetailMutasiAsetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $detail = $this->resource;

        return [
            'Id' => $detail->Id,
            'AsetId' => $detail->AsetId,
            'NamaAset' => $this->whenLoaded('aset', fn () => $detail->aset?->Nama),
            'KodeAset' => $this->whenLoaded('aset', fn () => $detail->aset?->KodeAset),
            'Status' => $detail->Status,
            'Catatan' => $detail->Catatan,
            'AlasanPenolakan' => $detail->AlasanPenolakan,
            'DiputuskanOleh' => $detail->DiputuskanOleh,
            'NamaDiputuskanOleh' => $this->whenLoaded('diputuskanOleh', fn () => $detail->diputuskanOleh?->Nama),
            'DiputuskanPada' => $detail->DiputuskanPada,
            'DipindaiOleh' => $detail->DipindaiOleh,
            'NamaDipindaiOleh' => $this->whenLoaded('dipindaiOleh', fn () => $detail->dipindaiOleh?->Nama),
            'DipindaiPada' => $detail->DipindaiPada,
            'DibuatPada' => $detail->DibuatPada,
        ];
    }
}
