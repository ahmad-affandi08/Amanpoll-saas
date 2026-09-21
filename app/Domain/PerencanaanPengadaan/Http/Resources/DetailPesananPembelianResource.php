<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Resources;

use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\DetailPesananPembelian;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class DetailPesananPembelianResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var DetailPesananPembelian $detail */
        $detail = $this->resource;

        return [
            'Id' => $detail->Id,
            'PesananPembelianId' => $detail->PesananPembelianId,
            'JenisItem' => $detail->JenisItem,
            'SukuCadangId' => $detail->SukuCadangId,
            'NamaSukuCadang' => $this->whenLoaded('sukuCadang', fn (): ?string => $detail->sukuCadang?->Nama),
            'Deskripsi' => $detail->Deskripsi,
            'Jumlah' => $detail->Jumlah,
            'Satuan' => $detail->Satuan,
            'HargaSatuan' => $detail->HargaSatuan,
            'Diskon' => $detail->Diskon,
            'Pajak' => $detail->Pajak,
            'Total' => $detail->Total,
        ];
    }
}
