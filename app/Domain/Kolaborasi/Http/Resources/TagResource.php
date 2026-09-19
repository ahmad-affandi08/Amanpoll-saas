<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Http\Resources;

use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TagResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Tag $tag */
        $tag = $this->resource;

        return [
            'Id' => $tag->Id,
            'Nama' => $tag->Nama,
            'Warna' => $tag->Warna,
            'DibuatPada' => $tag->DibuatPada->toIso8601String(),
        ];
    }
}
