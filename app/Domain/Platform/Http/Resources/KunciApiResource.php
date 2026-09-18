<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Resources;

use App\Domain\Platform\Infrastructure\Persistence\Models\KunciApi;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class KunciApiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var KunciApi $kunciApi */
        $kunciApi = $this->resource;

        return [
            'Id' => $kunciApi->Id,
            'Nama' => $kunciApi->Nama,
            'AwalanKunci' => $kunciApi->AwalanKunci,
            'Cakupan' => $kunciApi->Cakupan,
            'AlamatIpDiizinkan' => $kunciApi->AlamatIpDiizinkan,
            'KadaluarsaPada' => $kunciApi->KadaluarsaPada?->toIso8601String(),
            'TerakhirDipakaiPada' => $kunciApi->TerakhirDipakaiPada?->toIso8601String(),
            'Status' => $kunciApi->Status,
            'DibuatPada' => $kunciApi->DibuatPada->toIso8601String(),
        ];
    }
}
