<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Http\Resources;

use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\NilaiKolomKustom;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class NilaiKolomKustomResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var NilaiKolomKustom $nilai */
        $nilai = $this->resource;

        return [
            'Id' => $nilai->Id,
            'DefinisiKolomKustomId' => $nilai->DefinisiKolomKustomId,
            'JenisEntitas' => $nilai->JenisEntitas,
            'EntitasId' => $nilai->EntitasId,
            'Nilai' => $nilai->Nilai,
            'Definisi' => new DefinisiKolomKustomResource($this->whenLoaded('definisiKolomKustom')),
            'DibuatPada' => $nilai->DibuatPada->toIso8601String(),
        ];
    }
}
