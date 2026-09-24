<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Http\Resources;

use App\Domain\Persediaan\Infrastructure\Persistence\Models\Gudang;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class GudangResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Gudang $gudang */
        $gudang = $this->resource;

        return [
            'Id' => $gudang->Id,
            'LokasiId' => $gudang->LokasiId,
            'NamaLokasi' => $this->whenLoaded('lokasi', fn () => $gudang->lokasi?->Nama),
            'UnitPengelolaId' => $gudang->UnitPengelolaId,
            'KodeUnitPengelola' => $this->whenLoaded('unitPengelola', fn () => $gudang->unitPengelola?->Kode),
            'NamaUnitPengelola' => $this->whenLoaded('unitPengelola', fn () => $gudang->unitPengelola?->Nama),
            'Kode' => $gudang->Kode,
            'Nama' => $gudang->Nama,
            'PenanggungJawabId' => $gudang->PenanggungJawabId,
            'NamaPenanggungJawab' => $this->whenLoaded('penanggungJawab', fn () => $gudang->penanggungJawab?->Nama),
            'Status' => $gudang->Status,
            'JumlahLokasiGudang' => $this->whenCounted('lokasiGudang'),
            'DibuatPada' => $gudang->DibuatPada->toIso8601String(),
        ];
    }
}
