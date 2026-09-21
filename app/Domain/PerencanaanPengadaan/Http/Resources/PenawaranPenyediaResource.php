<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Resources;

use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PenawaranPenyedia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PenawaranPenyediaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var PenawaranPenyedia $penawaran */
        $penawaran = $this->resource;

        return [
            'Id' => $penawaran->Id,
            'PermintaanPenawaranId' => $penawaran->PermintaanPenawaranId,
            'PenyediaId' => $penawaran->PenyediaId,
            'NamaPenyedia' => $this->whenLoaded('penyedia', fn (): ?string => $penawaran->penyedia?->Nama),
            'NomorPenawaran' => $penawaran->NomorPenawaran,
            'TanggalPenawaran' => $penawaran->TanggalPenawaran->toDateString(),
            'BerlakuSampai' => $penawaran->BerlakuSampai?->toDateString(),
            'MataUang' => $penawaran->MataUang,
            'Subtotal' => $penawaran->Subtotal,
            'Pajak' => $penawaran->Pajak,
            'Diskon' => $penawaran->Diskon,
            'Total' => $penawaran->Total,
            'Status' => $penawaran->Status,
            'Catatan' => $penawaran->Catatan,
            'Detail' => DetailPenawaranPenyediaResource::collection($this->whenLoaded('detail')),
            'PermintaanPenawaran' => new PermintaanPenawaranResource($this->whenLoaded('permintaanPenawaran')),
            'DibuatPada' => $penawaran->DibuatPada->toIso8601String(),
        ];
    }
}
