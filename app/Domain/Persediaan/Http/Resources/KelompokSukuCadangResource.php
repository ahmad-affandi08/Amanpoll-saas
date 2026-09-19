<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Http\Resources;

use App\Domain\Persediaan\Infrastructure\Persistence\Models\KelompokSukuCadang;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class KelompokSukuCadangResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var KelompokSukuCadang $kelompokSukuCadang */
        $kelompokSukuCadang = $this->resource;

        return [
            'Id' => $kelompokSukuCadang->Id,
            'SukuCadangId' => $kelompokSukuCadang->SukuCadangId,
            'NomorBatch' => $kelompokSukuCadang->NomorBatch,
            'TanggalProduksi' => $kelompokSukuCadang->TanggalProduksi?->toDateString(),
            'TanggalKadaluarsa' => $kelompokSukuCadang->TanggalKadaluarsa?->toDateString(),
            'HargaPerolehan' => $kelompokSukuCadang->HargaPerolehan,
            'DibuatPada' => $kelompokSukuCadang->DibuatPada->toIso8601String(),
        ];
    }
}
