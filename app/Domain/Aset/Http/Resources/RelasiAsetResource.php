<?php

declare(strict_types=1);

namespace App\Domain\Aset\Http\Resources;

use App\Domain\Aset\Infrastructure\Persistence\Models\RelasiAset;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class RelasiAsetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var RelasiAset $relasi */
        $relasi = $this->resource;

        return [
            'Id' => $relasi->Id,
            'AsetIndukId' => $relasi->AsetIndukId,
            'NamaAsetInduk' => $this->whenLoaded('asetInduk', fn () => $relasi->asetInduk?->Nama),
            'AsetAnakId' => $relasi->AsetAnakId,
            'NamaAsetAnak' => $this->whenLoaded('asetAnak', fn () => $relasi->asetAnak?->Nama),
            'JenisRelasi' => $relasi->JenisRelasi,
            'Jumlah' => $relasi->Jumlah,
            'MulaiPada' => $relasi->MulaiPada?->toDateString(),
            'SelesaiPada' => $relasi->SelesaiPada?->toDateString(),
            'DibuatPada' => $relasi->DibuatPada->toIso8601String(),
        ];
    }
}
