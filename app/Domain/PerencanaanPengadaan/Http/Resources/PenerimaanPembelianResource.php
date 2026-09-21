<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Resources;

use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PenerimaanPembelian;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PenerimaanPembelianResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var PenerimaanPembelian $penerimaan */
        $penerimaan = $this->resource;

        return [
            'Id' => $penerimaan->Id,
            'Nomor' => $penerimaan->Nomor,
            'PesananPembelianId' => $penerimaan->PesananPembelianId,
            'NomorPesananPembelian' => $this->whenLoaded('pesananPembelian', fn (): ?string => $penerimaan->pesananPembelian?->Nomor),
            'NamaPenyedia' => $this->whenLoaded('pesananPembelian', fn (): ?string => $penerimaan->pesananPembelian?->penyedia?->Nama),
            'GudangId' => $penerimaan->GudangId,
            'NamaGudang' => $this->whenLoaded('gudang', fn (): ?string => $penerimaan->gudang?->Nama),
            'TanggalTerima' => $penerimaan->TanggalTerima->toIso8601String(),
            'NomorSuratJalan' => $penerimaan->NomorSuratJalan,
            'NamaPenerima' => $this->whenLoaded('diterimaOleh', fn (): ?string => $penerimaan->diterimaOleh?->Nama),
            'Status' => $penerimaan->Status,
            'Catatan' => $penerimaan->Catatan,
            'JumlahItem' => $this->whenCounted('detail'),
            'Detail' => DetailPenerimaanPembelianResource::collection($this->whenLoaded('detail')),
            'DibuatPada' => $penerimaan->DibuatPada->toIso8601String(),
        ];
    }
}
