<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Resources;

use App\Domain\Platform\Infrastructure\Persistence\Models\PerangkatPengguna;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PerangkatPenggunaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var PerangkatPengguna $perangkat */
        $perangkat = $this->resource;

        return [
            'Id' => $perangkat->Id,
            'NamaPerangkat' => $perangkat->NamaPerangkat,
            'Platform' => $perangkat->Platform,
            'Status' => $perangkat->Status,
            'TerakhirSinkronPada' => $perangkat->TerakhirSinkronPada?->toIso8601String(),
        ];
    }
}
