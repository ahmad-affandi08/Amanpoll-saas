<?php

declare(strict_types=1);

namespace App\Domain\Aset\Http\Resources;

use App\Domain\Aset\Infrastructure\Persistence\Models\PembacaanMeterAset;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PembacaanMeterAsetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var PembacaanMeterAset $pembacaan */
        $pembacaan = $this->resource;

        return [
            'Id' => $pembacaan->Id,
            'MeterAsetId' => $pembacaan->MeterAsetId,
            'Nilai' => $pembacaan->Nilai,
            'DibacaPada' => $pembacaan->DibacaPada->toIso8601String(),
            'Sumber' => $pembacaan->Sumber,
            'NamaDicatatOleh' => $this->whenLoaded('dicatatOleh', fn () => $pembacaan->dicatatOleh?->Nama),
            'DibuatPada' => $pembacaan->DibuatPada->toIso8601String(),
        ];
    }
}
