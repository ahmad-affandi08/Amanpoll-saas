<?php

declare(strict_types=1);

namespace App\Domain\Aset\Http\Resources;

use App\Domain\Aset\Infrastructure\Persistence\Models\RiwayatPenanggungJawabAset;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class RiwayatPenanggungJawabAsetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var RiwayatPenanggungJawabAset $riwayat */
        $riwayat = $this->resource;

        return [
            'Id' => $riwayat->Id,
            'PenggunaId' => $riwayat->PenggunaId,
            'NamaPengguna' => $this->whenLoaded('pengguna', fn () => $riwayat->pengguna?->Nama),
            'UnitOrganisasiId' => $riwayat->UnitOrganisasiId,
            'NamaUnitOrganisasi' => $this->whenLoaded('unitOrganisasi', fn () => $riwayat->unitOrganisasi?->Nama),
            'MulaiPada' => $riwayat->MulaiPada->toIso8601String(),
            'SelesaiPada' => $riwayat->SelesaiPada?->toIso8601String(),
            'Catatan' => $riwayat->Catatan,
        ];
    }
}
