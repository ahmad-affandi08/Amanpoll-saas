<?php

declare(strict_types=1);

namespace App\Domain\Persetujuan\Http\Resources;

use App\Domain\Persetujuan\Infrastructure\Persistence\Models\KeputusanPersetujuan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class KeputusanPersetujuanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var KeputusanPersetujuan $keputusan */
        $keputusan = $this->resource;
        /** @var Pengguna|null $penyetuju */
        $penyetuju = $keputusan->penyetuju;

        return [
            'Id' => $keputusan->Id,
            'PermintaanPersetujuanId' => $keputusan->PermintaanPersetujuanId,
            'TahapPersetujuanId' => $keputusan->TahapPersetujuanId,
            'PenyetujuId' => $keputusan->PenyetujuId,
            'NamaPenyetuju' => $this->whenLoaded('penyetuju', fn () => $penyetuju?->Nama),
            'Keputusan' => $keputusan->Keputusan,
            'Catatan' => $keputusan->Catatan,
            'DiputuskanPada' => $keputusan->DiputuskanPada->toIso8601String(),
        ];
    }
}
