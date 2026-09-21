<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Resources;

use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PermintaanPenawaran;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PermintaanPenawaranResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var PermintaanPenawaran $rfq */
        $rfq = $this->resource;

        return [
            'Id' => $rfq->Id,
            'Nomor' => $rfq->Nomor,
            'PermintaanPembelianId' => $rfq->PermintaanPembelianId,
            'PermintaanPembelian' => new PermintaanPembelianResource($this->whenLoaded('permintaanPembelian')),
            'TanggalDibuka' => $rfq->TanggalDibuka->toIso8601String(),
            'BatasPenawaran' => $rfq->BatasPenawaran?->toIso8601String(),
            'Status' => $rfq->Status,
            'Catatan' => $rfq->Catatan,
            'NamaPembuat' => $this->whenLoaded('dibuatOleh', fn (): ?string => $rfq->dibuatOleh?->Nama),
            'JumlahPenyediaDiundang' => $this->whenCounted('penyediaDiundang'),
            'JumlahPenawaran' => $this->whenCounted('penawaran'),
            'PenyediaDiundang' => PenyediaPermintaanPenawaranResource::collection($this->whenLoaded('penyediaDiundang')),
            'Penawaran' => PenawaranPenyediaResource::collection($this->whenLoaded('penawaran')),
            'DibuatPada' => $rfq->DibuatPada->toIso8601String(),
        ];
    }
}
