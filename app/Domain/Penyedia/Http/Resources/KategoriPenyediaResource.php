<?php

declare(strict_types=1);

namespace App\Domain\Penyedia\Http\Resources;

use App\Domain\Penyedia\Infrastructure\Persistence\Models\KategoriPenyedia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class KategoriPenyediaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var KategoriPenyedia $kategoriPenyedia */
        $kategoriPenyedia = $this->resource;

        return [
            'Id' => $kategoriPenyedia->Id,
            'Kode' => $kategoriPenyedia->Kode,
            'Nama' => $kategoriPenyedia->Nama,
            'DibuatPada' => $kategoriPenyedia->DibuatPada->toIso8601String(),
        ];
    }
}
