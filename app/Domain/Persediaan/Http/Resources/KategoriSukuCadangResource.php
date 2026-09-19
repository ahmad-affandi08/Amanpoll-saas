<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Http\Resources;

use App\Domain\Persediaan\Infrastructure\Persistence\Models\KategoriSukuCadang;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class KategoriSukuCadangResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var KategoriSukuCadang $kategoriSukuCadang */
        $kategoriSukuCadang = $this->resource;

        return [
            'Id' => $kategoriSukuCadang->Id,
            'IndukId' => $kategoriSukuCadang->IndukId,
            'NamaInduk' => $this->whenLoaded('induk', fn () => $kategoriSukuCadang->induk?->Nama),
            'Kode' => $kategoriSukuCadang->Kode,
            'Nama' => $kategoriSukuCadang->Nama,
            'DibuatPada' => $kategoriSukuCadang->DibuatPada->toIso8601String(),
        ];
    }
}
