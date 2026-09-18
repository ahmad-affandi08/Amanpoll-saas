<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Resources;

use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PenggunaPeranResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var PenggunaPeran $penugasan */
        $penugasan = $this->resource;

        return [
            'Id' => $penugasan->Id,
            'PeranId' => $penugasan->PeranId,
            'NamaPeran' => $this->whenLoaded('peran', fn () => $penugasan->peran->Nama),
            'UnitOrganisasiId' => $penugasan->UnitOrganisasiId,
            'LokasiId' => $penugasan->LokasiId,
            'BerlakuMulai' => $penugasan->BerlakuMulai?->toIso8601String(),
            'BerlakuSampai' => $penugasan->BerlakuSampai?->toIso8601String(),
        ];
    }
}
