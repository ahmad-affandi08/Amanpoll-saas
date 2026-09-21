<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Resources;

use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PermintaanPembelian;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PermintaanPembelianResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var PermintaanPembelian $permintaan */
        $permintaan = $this->resource;

        return [
            'Id' => $permintaan->Id,
            'Nomor' => $permintaan->Nomor,
            'UnitOrganisasiId' => $permintaan->UnitOrganisasiId,
            'NamaUnitOrganisasi' => $this->whenLoaded('unitOrganisasi', fn (): ?string => $permintaan->unitOrganisasi?->Nama),
            'RencanaPengadaanId' => $permintaan->RencanaPengadaanId,
            'NomorRencanaPengadaan' => $this->whenLoaded('rencanaPengadaan', fn (): ?string => $permintaan->rencanaPengadaan?->Nomor),
            'PosAnggaranId' => $permintaan->PosAnggaranId,
            'NamaPosAnggaran' => $this->whenLoaded('posAnggaran', fn (): ?string => $permintaan->posAnggaran?->Nama),
            'TanggalPermintaan' => $permintaan->TanggalPermintaan->toDateString(),
            'TanggalDibutuhkan' => $permintaan->TanggalDibutuhkan?->toDateString(),
            'Prioritas' => $permintaan->Prioritas,
            'Status' => $permintaan->Status,
            'Alasan' => $permintaan->Alasan,
            'NamaPeminta' => $this->whenLoaded('dimintaOleh', fn (): ?string => $permintaan->dimintaOleh?->Nama),
            'TotalEstimasi' => $permintaan->TotalEstimasi,
            'JumlahItem' => $this->whenCounted('detail'),
            'Detail' => DetailPermintaanPembelianResource::collection($this->whenLoaded('detail')),
            'DibuatPada' => $permintaan->DibuatPada->toIso8601String(),
            'DiperbaruiPada' => $permintaan->DiperbaruiPada->toIso8601String(),
        ];
    }
}
