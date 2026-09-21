<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Http\Resources;

use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\StandarKepatuhan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class StandarKepatuhanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var StandarKepatuhan $standar */
        $standar = $this->resource;

        return [
            'Id' => $standar->Id,
            'Kode' => $standar->Kode,
            'Nama' => $standar->Nama,
            'Penerbit' => $standar->Penerbit,
            'VersiStandar' => $standar->VersiStandar,
            'JenisIndustri' => $standar->JenisIndustri,
            'Deskripsi' => $standar->Deskripsi,
            'Aktif' => $standar->Aktif,
            'JumlahPersyaratan' => $this->whenCounted('persyaratan'),
            'Persyaratan' => PersyaratanKepatuhanResource::collection($this->whenLoaded('persyaratan')),
            'DibuatPada' => $standar->DibuatPada->toIso8601String(),
        ];
    }
}
