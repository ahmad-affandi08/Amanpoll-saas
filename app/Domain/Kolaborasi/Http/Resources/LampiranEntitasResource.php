<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Http\Resources;

use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\LampiranEntitas;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class LampiranEntitasResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var LampiranEntitas $lampiran */
        $lampiran = $this->resource;

        return [
            'Id' => $lampiran->Id,
            'JenisEntitas' => $lampiran->JenisEntitas,
            'EntitasId' => $lampiran->EntitasId,
            'BerkasId' => $lampiran->BerkasId,
            'Kategori' => $lampiran->Kategori,
            'Keterangan' => $lampiran->Keterangan,
            'Berkas' => new BerkasResource($this->whenLoaded('berkas')),
            'DibuatPada' => $lampiran->DibuatPada->toIso8601String(),
        ];
    }
}
