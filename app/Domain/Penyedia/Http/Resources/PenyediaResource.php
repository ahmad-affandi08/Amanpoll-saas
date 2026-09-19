<?php

declare(strict_types=1);

namespace App\Domain\Penyedia\Http\Resources;

use App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PenyediaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Penyedia $penyedia */
        $penyedia = $this->resource;

        return [
            'Id' => $penyedia->Id,
            'Kode' => $penyedia->Kode,
            'Nama' => $penyedia->Nama,
            'NamaLegal' => $penyedia->NamaLegal,
            'NomorIdentitasPajak' => $penyedia->NomorIdentitasPajak,
            'Email' => $penyedia->Email,
            'Telepon' => $penyedia->Telepon,
            'Website' => $penyedia->Website,
            'Alamat' => $penyedia->Alamat,
            'Kota' => $penyedia->Kota,
            'Provinsi' => $penyedia->Provinsi,
            'Negara' => $penyedia->Negara,
            'Status' => $penyedia->Status,
            'KategoriPenyediaId' => $this->whenLoaded('kategoriPenyedia', fn () => $penyedia->kategoriPenyedia->pluck('Id')->all()),
            'NamaKategoriPenyedia' => $this->whenLoaded('kategoriPenyedia', fn () => $penyedia->kategoriPenyedia->pluck('Nama')->all()),
            'DibuatPada' => $penyedia->DibuatPada->toIso8601String(),
        ];
    }
}
