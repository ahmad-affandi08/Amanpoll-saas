<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Resources;

use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\DetailRencanaPengadaan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class DetailRencanaPengadaanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var DetailRencanaPengadaan $detail */
        $detail = $this->resource;

        return [
            'Id' => $detail->Id,
            'UsulanAsetId' => $detail->UsulanAsetId,
            'NomorUsulan' => $this->whenLoaded('usulanAset', fn (): ?string => $detail->usulanAset?->Nomor),
            'SukuCadangId' => $detail->SukuCadangId,
            'NamaSukuCadang' => $this->whenLoaded('sukuCadang', fn (): ?string => $detail->sukuCadang?->Nama),
            'Deskripsi' => $detail->Deskripsi,
            'Jumlah' => $detail->Jumlah,
            'Satuan' => $detail->Satuan,
            'HargaEstimasi' => $detail->HargaEstimasi,
            'BulanRencana' => $detail->BulanRencana,
        ];
    }
}
