<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Http\Resources;

use App\Domain\Persediaan\Infrastructure\Persistence\Models\LokasiGudang;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class LokasiGudangResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var LokasiGudang $lokasiGudang */
        $lokasiGudang = $this->resource;

        return [
            'Id' => $lokasiGudang->Id,
            'GudangId' => $lokasiGudang->GudangId,
            'IndukId' => $lokasiGudang->IndukId,
            'NamaInduk' => $this->whenLoaded('induk', fn () => $lokasiGudang->induk?->Nama),
            'Kode' => $lokasiGudang->Kode,
            'Nama' => $lokasiGudang->Nama,
            'DibuatPada' => $lokasiGudang->DibuatPada->toIso8601String(),
        ];
    }
}
