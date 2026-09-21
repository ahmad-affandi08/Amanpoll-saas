<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Resources;

use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\DetailPermintaanPembelian;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class DetailPermintaanPembelianResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var DetailPermintaanPembelian $detail */
        $detail = $this->resource;

        return [
            'Id' => $detail->Id,
            'PermintaanPembelianId' => $detail->PermintaanPembelianId,
            'JenisItem' => $detail->JenisItem,
            'AsetReferensiId' => $detail->AsetReferensiId,
            'NamaAsetReferensi' => $this->whenLoaded('asetReferensi', fn (): ?string => $detail->asetReferensi?->Nama),
            'SukuCadangId' => $detail->SukuCadangId,
            'NamaSukuCadang' => $this->whenLoaded('sukuCadang', fn (): ?string => $detail->sukuCadang?->Nama),
            'Deskripsi' => $detail->Deskripsi,
            'Jumlah' => $detail->Jumlah,
            'Satuan' => $detail->Satuan,
            'HargaEstimasi' => $detail->HargaEstimasi,
            'Spesifikasi' => $detail->Spesifikasi,
        ];
    }
}
