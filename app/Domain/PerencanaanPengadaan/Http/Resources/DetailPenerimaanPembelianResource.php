<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Resources;

use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\DetailPenerimaanPembelian;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class DetailPenerimaanPembelianResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var DetailPenerimaanPembelian $detail */
        $detail = $this->resource;

        return [
            'Id' => $detail->Id,
            'DetailPesananPembelianId' => $detail->DetailPesananPembelianId,
            'Deskripsi' => $this->whenLoaded('detailPesananPembelian', fn (): ?string => $detail->detailPesananPembelian?->Deskripsi),
            'SukuCadangId' => $detail->SukuCadangId,
            'JumlahDipesan' => $detail->JumlahDipesan,
            'JumlahDiterima' => $detail->JumlahDiterima,
            'JumlahDitolak' => $detail->JumlahDitolak,
            'Kondisi' => $detail->Kondisi,
            'NomorSeri' => $detail->NomorSeriJson ?? [],
            'Catatan' => $detail->Catatan,
        ];
    }
}
