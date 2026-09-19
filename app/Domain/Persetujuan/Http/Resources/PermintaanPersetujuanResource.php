<?php

declare(strict_types=1);

namespace App\Domain\Persetujuan\Http\Resources;

use App\Domain\Persetujuan\Infrastructure\Persistence\Models\AlurPersetujuan;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\PermintaanPersetujuan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PermintaanPersetujuanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var PermintaanPersetujuan $permintaan */
        $permintaan = $this->resource;
        /** @var Pengguna|null $peminta */
        $peminta = $permintaan->dimintaOleh;
        /** @var AlurPersetujuan|null $alur */
        $alur = $permintaan->alurPersetujuan;

        return [
            'Id' => $permintaan->Id,
            'AlurPersetujuanId' => $permintaan->AlurPersetujuanId,
            'NamaAlur' => $this->whenLoaded('alurPersetujuan', fn () => $alur?->Nama),
            'JenisEntitas' => $permintaan->JenisEntitas,
            'EntitasId' => $permintaan->EntitasId,
            'TahapSaatIni' => $permintaan->TahapSaatIni,
            'Status' => $permintaan->Status,
            'DimintaOleh' => $permintaan->DimintaOleh,
            'NamaPeminta' => $this->whenLoaded('dimintaOleh', fn () => $peminta?->Nama),
            'DimintaPada' => $permintaan->DimintaPada->toIso8601String(),
            'SelesaiPada' => $permintaan->SelesaiPada?->toIso8601String(),
            'DataTambahan' => $permintaan->DataTambahan,
            'Keputusan' => KeputusanPersetujuanResource::collection($this->whenLoaded('keputusan')),
        ];
    }
}
