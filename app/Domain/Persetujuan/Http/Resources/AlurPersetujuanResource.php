<?php

declare(strict_types=1);

namespace App\Domain\Persetujuan\Http\Resources;

use App\Domain\Persetujuan\Infrastructure\Persistence\Models\AlurPersetujuan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AlurPersetujuanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var AlurPersetujuan $alur */
        $alur = $this->resource;

        return [
            'Id' => $alur->Id,
            'Kode' => $alur->Kode,
            'Nama' => $alur->Nama,
            'JenisEntitas' => $alur->JenisEntitas,
            'KondisiAktivasi' => $alur->KondisiAktivasi,
            'Aktif' => $alur->Aktif,
            'TahapPersetujuan' => TahapPersetujuanResource::collection($this->whenLoaded('tahapPersetujuan')),
            'DibuatPada' => $alur->DibuatPada->toIso8601String(),
        ];
    }
}
