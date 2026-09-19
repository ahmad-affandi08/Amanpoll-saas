<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Resources;

use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class OrganisasiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Organisasi $organisasi */
        $organisasi = $this->resource;

        return [
            'Id' => $organisasi->Id,
            'Kode' => $organisasi->Kode,
            'Nama' => $organisasi->Nama,
            'NamaLegal' => $organisasi->NamaLegal,
            'JenisUsaha' => $organisasi->JenisUsaha,
            'NomorIdentitasPajak' => $organisasi->NomorIdentitasPajak,
            'Email' => $organisasi->Email,
            'Telepon' => $organisasi->Telepon,
            'Alamat' => $organisasi->Alamat,
            'Negara' => $organisasi->Negara,
            'Provinsi' => $organisasi->Provinsi,
            'Kota' => $organisasi->Kota,
            'ZonaWaktu' => $organisasi->ZonaWaktu,
            'LogoUrl' => $organisasi->LogoUrl,
            'Status' => $organisasi->Status,
            'DibuatPada' => $organisasi->DibuatPada->toIso8601String(),
        ];
    }
}
