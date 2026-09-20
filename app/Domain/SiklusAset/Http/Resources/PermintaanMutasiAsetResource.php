<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Http\Resources;

use App\Domain\SiklusAset\Infrastructure\Persistence\Models\PermintaanMutasiAset;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property PermintaanMutasiAset $resource
 */
final class PermintaanMutasiAsetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $permintaan = $this->resource;

        return [
            'Id' => $permintaan->Id,
            'Nomor' => $permintaan->Nomor,
            'JenisMutasi' => $permintaan->JenisMutasi,
            'UnitAsalId' => $permintaan->UnitAsalId,
            'NamaUnitAsal' => $this->whenLoaded('unitAsal', fn () => $permintaan->unitAsal?->Nama),
            'UnitTujuanId' => $permintaan->UnitTujuanId,
            'NamaUnitTujuan' => $this->whenLoaded('unitTujuan', fn () => $permintaan->unitTujuan?->Nama),
            'LokasiAsalId' => $permintaan->LokasiAsalId,
            'NamaLokasiAsal' => $this->whenLoaded('lokasiAsal', fn () => $permintaan->lokasiAsal?->Nama),
            'LokasiTujuanId' => $permintaan->LokasiTujuanId,
            'NamaLokasiTujuan' => $this->whenLoaded('lokasiTujuan', fn () => $permintaan->lokasiTujuan?->Nama),
            'Alasan' => $permintaan->Alasan,
            'Status' => $permintaan->Status,
            'DimintaOleh' => $permintaan->DimintaOleh,
            'NamaDimintaOleh' => $this->whenLoaded('dimintaOleh', fn () => $permintaan->dimintaOleh?->Nama),
            'DimintaPada' => $permintaan->DimintaPada,
            'DisetujuiPada' => $permintaan->DisetujuiPada,
            'SelesaiPada' => $permintaan->SelesaiPada,
            'DetailMutasiAset' => DetailMutasiAsetResource::collection($this->whenLoaded('detailMutasiAset')),
            'Versi' => $permintaan->Versi,
            'DibuatPada' => $permintaan->DibuatPada,
        ];
    }
}
