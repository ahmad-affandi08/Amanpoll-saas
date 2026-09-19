<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Http\Resources;

use App\Domain\Notifikasi\Infrastructure\Persistence\Models\Notifikasi;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class NotifikasiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Notifikasi $notifikasi */
        $notifikasi = $this->resource;

        return [
            'Id' => $notifikasi->Id,
            'Kanal' => $notifikasi->Kanal,
            'JenisPeristiwa' => $notifikasi->JenisPeristiwa,
            'Judul' => $notifikasi->Judul,
            'Isi' => $notifikasi->Isi,
            'JenisEntitas' => $notifikasi->JenisEntitas,
            'EntitasId' => $notifikasi->EntitasId,
            'Status' => $notifikasi->Status,
            'DibacaPada' => $notifikasi->DibacaPada?->toIso8601String(),
            'DibuatPada' => $notifikasi->DibuatPada->toIso8601String(),
        ];
    }
}
