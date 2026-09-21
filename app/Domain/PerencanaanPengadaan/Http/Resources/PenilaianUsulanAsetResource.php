<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Resources;

use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PenilaianUsulanAset;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PenilaianUsulanAsetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var PenilaianUsulanAset $penilaian */
        $penilaian = $this->resource;

        return [
            'Id' => $penilaian->Id,
            'Kriteria' => $penilaian->Kriteria,
            'Bobot' => $penilaian->Bobot,
            'Nilai' => $penilaian->Nilai,
            'Skor' => $penilaian->Skor,
            'NamaPenilai' => $this->whenLoaded('dinilaiOleh', fn (): ?string => $penilaian->dinilaiOleh?->Nama),
            'DinilaiPada' => $penilaian->DinilaiPada->toIso8601String(),
        ];
    }
}
