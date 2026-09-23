<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Resources;

use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class LokasiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Lokasi $lokasi */
        $lokasi = $this->resource;

        return [
            'Id' => $lokasi->Id,
            'IndukId' => $lokasi->IndukId,
            'UnitOrganisasiId' => $lokasi->UnitOrganisasiId,
            'KategoriLokasiId' => $lokasi->KategoriLokasiId,
            'Kode' => $lokasi->Kode,
            'KodeRuangAspak' => $lokasi->KodeRuangAspak,
            'Nama' => $lokasi->Nama,
            'Alamat' => $lokasi->Alamat,
            'Lantai' => $lokasi->Lantai,
            'Latitude' => $lokasi->Latitude,
            'Longitude' => $lokasi->Longitude,
            'ZonaWaktu' => $lokasi->ZonaWaktu,
            'Status' => $lokasi->Status,
            'NamaKategoriLokasi' => $this->whenLoaded('kategoriLokasi', fn () => $lokasi->kategoriLokasi?->Nama),
            'NamaUnitOrganisasi' => $this->whenLoaded('unitOrganisasi', fn () => $lokasi->unitOrganisasi?->Nama),
            'DibuatPada' => $lokasi->DibuatPada->toIso8601String(),
        ];
    }
}
