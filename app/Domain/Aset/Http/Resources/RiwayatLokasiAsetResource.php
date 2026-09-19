<?php

declare(strict_types=1);

namespace App\Domain\Aset\Http\Resources;

use App\Domain\Aset\Infrastructure\Persistence\Models\RiwayatLokasiAset;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class RiwayatLokasiAsetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var RiwayatLokasiAset $riwayat */
        $riwayat = $this->resource;

        return [
            'Id' => $riwayat->Id,
            'LokasiAsalId' => $riwayat->LokasiAsalId,
            'NamaLokasiAsal' => $this->whenLoaded('lokasiAsal', fn () => $riwayat->lokasiAsal?->Nama),
            'LokasiTujuanId' => $riwayat->LokasiTujuanId,
            'NamaLokasiTujuan' => $this->whenLoaded('lokasiTujuan', fn () => $riwayat->lokasiTujuan?->Nama),
            'JenisPerpindahan' => $riwayat->JenisPerpindahan,
            'Alasan' => $riwayat->Alasan,
            'NamaDipindahkanOleh' => $this->whenLoaded('dipindahkanOleh', fn () => $riwayat->dipindahkanOleh?->Nama),
            'DipindahkanPada' => $riwayat->DipindahkanPada->toIso8601String(),
        ];
    }
}
