<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Http\Resources;

use App\Domain\Persediaan\Infrastructure\Persistence\Models\ReservasiSukuCadang;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ReservasiSukuCadangResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var ReservasiSukuCadang $reservasi */
        $reservasi = $this->resource;

        return [
            'Id' => $reservasi->Id,
            'PerintahKerjaId' => $reservasi->PerintahKerjaId,
            'GudangId' => $reservasi->GudangId,
            'NamaGudang' => $this->whenLoaded('gudang', fn () => $reservasi->gudang?->Nama),
            'SukuCadangId' => $reservasi->SukuCadangId,
            'NamaSukuCadang' => $this->whenLoaded('sukuCadang', fn () => $reservasi->sukuCadang?->Nama),
            'KodeSukuCadang' => $this->whenLoaded('sukuCadang', fn () => $reservasi->sukuCadang?->Kode),
            'Jumlah' => $reservasi->Jumlah,
            'Status' => $reservasi->Status,
            'KadaluarsaPada' => $reservasi->KadaluarsaPada?->toIso8601String(),
            'NamaDibuatOleh' => $this->whenLoaded('dibuatOleh', fn () => $reservasi->dibuatOleh?->Nama),
            'DibuatPada' => $reservasi->DibuatPada->toIso8601String(),
        ];
    }
}
