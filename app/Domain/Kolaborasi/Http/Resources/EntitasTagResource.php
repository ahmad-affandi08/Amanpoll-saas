<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Http\Resources;

use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\EntitasTag;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class EntitasTagResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var EntitasTag $entitasTag */
        $entitasTag = $this->resource;

        return [
            'Id' => $entitasTag->Id,
            'TagId' => $entitasTag->TagId,
            'JenisEntitas' => $entitasTag->JenisEntitas,
            'EntitasId' => $entitasTag->EntitasId,
            'Tag' => new TagResource($this->whenLoaded('tag')),
            'DibuatPada' => $entitasTag->DibuatPada->toIso8601String(),
        ];
    }
}
