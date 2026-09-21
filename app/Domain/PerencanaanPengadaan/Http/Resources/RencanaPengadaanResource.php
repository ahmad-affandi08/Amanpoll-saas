<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Resources;

use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\RencanaPengadaan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class RencanaPengadaanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var RencanaPengadaan $rencana */
        $rencana = $this->resource;

        return [
            'Id' => $rencana->Id,
            'Nomor' => $rencana->Nomor,
            'Nama' => $rencana->Nama,
            'Tahun' => $rencana->Tahun,
            'PosAnggaranId' => $rencana->PosAnggaranId,
            'NamaPosAnggaran' => $this->whenLoaded('posAnggaran', fn (): ?string => $rencana->posAnggaran?->Nama),
            'Status' => $rencana->Status,
            'TotalEstimasi' => $rencana->TotalEstimasi,
            'NamaPembuat' => $this->whenLoaded('dibuatOleh', fn (): ?string => $rencana->dibuatOleh?->Nama),
            'Detail' => DetailRencanaPengadaanResource::collection($this->whenLoaded('detail')),
            'DibuatPada' => $rencana->DibuatPada->toIso8601String(),
            'DiperbaruiPada' => $rencana->DiperbaruiPada->toIso8601String(),
        ];
    }
}
