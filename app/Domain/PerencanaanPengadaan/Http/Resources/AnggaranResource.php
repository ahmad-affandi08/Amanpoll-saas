<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Resources;

use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\Anggaran;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AnggaranResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Anggaran $anggaran */
        $anggaran = $this->resource;

        return [
            'Id' => $anggaran->Id,
            'Kode' => $anggaran->Kode,
            'Nama' => $anggaran->Nama,
            'Tahun' => $anggaran->Tahun,
            'MataUang' => $anggaran->MataUang,
            'Jumlah' => $anggaran->Jumlah,
            'Status' => $anggaran->Status,
            'UnitOrganisasiId' => $anggaran->UnitOrganisasiId,
            'NamaUnitOrganisasi' => $this->whenLoaded('unitOrganisasi', fn (): ?string => $anggaran->unitOrganisasi?->Nama),
            'JumlahPos' => $this->whenCounted('posAnggaran'),
            'PosAnggaran' => PosAnggaranResource::collection($this->whenLoaded('posAnggaran')),
            'DibuatPada' => $anggaran->DibuatPada->toIso8601String(),
            'DiperbaruiPada' => $anggaran->DiperbaruiPada->toIso8601String(),
        ];
    }
}
