<?php

declare(strict_types=1);

namespace App\Domain\IntegrasiAudit\Http\Resources;

use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\CatatanAudit;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CatatanAuditResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var CatatanAudit $catatan */
        $catatan = $this->resource;
        /** @var Pengguna|null $pengguna */
        $pengguna = $catatan->pengguna;

        return [
            'Id' => $catatan->Id,
            'PenggunaId' => $catatan->PenggunaId,
            'NamaPengguna' => $this->whenLoaded('pengguna', fn () => $pengguna?->Nama),
            'Aksi' => $catatan->Aksi,
            'JenisEntitas' => $catatan->JenisEntitas,
            'EntitasId' => $catatan->EntitasId,
            'DataSebelum' => $catatan->DataSebelum,
            'DataSesudah' => $catatan->DataSesudah,
            'AlamatIp' => $catatan->AlamatIp,
            'AgenPengguna' => $catatan->AgenPengguna,
            'KorelasiId' => $catatan->KorelasiId,
            'DibuatPada' => $catatan->DibuatPada->toIso8601String(),
        ];
    }
}
