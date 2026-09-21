<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Resources;

use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PenyediaPermintaanPenawaran;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PenyediaPermintaanPenawaranResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var PenyediaPermintaanPenawaran $undangan */
        $undangan = $this->resource;

        return [
            'Id' => $undangan->Id,
            'PenyediaId' => $undangan->PenyediaId,
            'NamaPenyedia' => $this->whenLoaded('penyedia', fn (): ?string => $undangan->penyedia?->Nama),
            'Status' => $undangan->Status,
            'DikirimPada' => $undangan->DikirimPada?->toIso8601String(),
            'DilihatPada' => $undangan->DilihatPada?->toIso8601String(),
        ];
    }
}
