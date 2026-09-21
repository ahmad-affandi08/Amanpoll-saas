<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Resources;

use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\TagihanPenyedia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TagihanPenyediaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var TagihanPenyedia $tagihan */
        $tagihan = $this->resource;

        return [
            'Id' => $tagihan->Id,
            'PenyediaId' => $tagihan->PenyediaId,
            'NamaPenyedia' => $this->whenLoaded('penyedia', fn (): ?string => $tagihan->penyedia?->Nama),
            'PesananPembelianId' => $tagihan->PesananPembelianId,
            'NomorPesananPembelian' => $this->whenLoaded('pesananPembelian', fn (): ?string => $tagihan->pesananPembelian?->Nomor),
            'NomorTagihan' => $tagihan->NomorTagihan,
            'TanggalTagihan' => $tagihan->TanggalTagihan->toDateString(),
            'JatuhTempo' => $tagihan->JatuhTempo?->toDateString(),
            'Subtotal' => $tagihan->Subtotal,
            'Pajak' => $tagihan->Pajak,
            'Total' => $tagihan->Total,
            'Sisa' => $tagihan->Sisa,
            'Status' => $tagihan->Status,
            'JumlahPembayaran' => $this->whenCounted('pembayaran'),
            'Pembayaran' => PembayaranPenyediaResource::collection($this->whenLoaded('pembayaran')),
            'DibuatPada' => $tagihan->DibuatPada->toIso8601String(),
        ];
    }
}
