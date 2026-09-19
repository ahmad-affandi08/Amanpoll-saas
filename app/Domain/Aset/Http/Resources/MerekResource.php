<?php

declare(strict_types=1);

namespace App\Domain\Aset\Http\Resources;

use App\Domain\Aset\Infrastructure\Persistence\Models\Merek;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class MerekResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Merek $merek */
        $merek = $this->resource;

        return [
            'Id' => $merek->Id,
            'Nama' => $merek->Nama,
            'NegaraAsal' => $merek->NegaraAsal,
            'Website' => $merek->Website,
            'DibuatPada' => $merek->DibuatPada->toIso8601String(),
        ];
    }
}
