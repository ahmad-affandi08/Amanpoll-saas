<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Http\Resources;

use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\PersyaratanKepatuhan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PersyaratanKepatuhanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var PersyaratanKepatuhan $persyaratan */
        $persyaratan = $this->resource;

        return [
            'Id' => $persyaratan->Id,
            'StandarKepatuhanId' => $persyaratan->StandarKepatuhanId,
            'NamaStandar' => $this->whenLoaded('standarKepatuhan', fn (): ?string => $persyaratan->standarKepatuhan?->Nama),
            'Kode' => $persyaratan->Kode,
            'Nama' => $persyaratan->Nama,
            'Deskripsi' => $persyaratan->Deskripsi,
            'BuktiYangDiperlukan' => $persyaratan->BuktiYangDiperlukan,
            'IntervalHari' => $persyaratan->IntervalHari,
            'JumlahAsetDitugaskan' => $this->whenCounted('kepatuhanAset'),
        ];
    }
}
