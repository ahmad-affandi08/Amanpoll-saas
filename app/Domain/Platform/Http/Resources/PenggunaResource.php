<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Resources;

use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PenggunaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Pengguna $pengguna */
        $pengguna = $this->resource;

        return [
            'Id' => $pengguna->Id,
            'Nama' => $pengguna->Nama,
            'Email' => $pengguna->Email,
            'Telepon' => $pengguna->Telepon,
            'NomorPegawai' => $pengguna->NomorPegawai,
            'Jabatan' => $pengguna->Jabatan,
            'JenisPengguna' => $pengguna->JenisPengguna,
            'Status' => $pengguna->Status,
            'UnitOrganisasiId' => $pengguna->UnitOrganisasiId,
            'TerakhirMasukPada' => $pengguna->TerakhirMasukPada?->toIso8601String(),
            'Peran' => PenggunaPeranResource::collection($this->whenLoaded('penggunaPeran')),
            'Perangkat' => PerangkatPenggunaResource::collection($this->whenLoaded('perangkat')),
            'DibuatPada' => $pengguna->DibuatPada->toIso8601String(),
        ];
    }
}
