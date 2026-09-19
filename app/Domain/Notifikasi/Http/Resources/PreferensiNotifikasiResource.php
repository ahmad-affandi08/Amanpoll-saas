<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Http\Resources;

use App\Domain\Notifikasi\Infrastructure\Persistence\Models\PreferensiNotifikasi;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PreferensiNotifikasiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var PreferensiNotifikasi $preferensi */
        $preferensi = $this->resource;

        return [
            'Id' => $preferensi->Id,
            'JenisPeristiwa' => $preferensi->JenisPeristiwa,
            'Kanal' => $preferensi->Kanal,
            'Aktif' => $preferensi->Aktif,
        ];
    }
}
