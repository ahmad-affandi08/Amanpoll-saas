<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Resources;

use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class UnitOrganisasiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var UnitOrganisasi $unit */
        $unit = $this->resource;

        return [
            'Id' => $unit->Id,
            'IndukId' => $unit->IndukId,
            'Kode' => $unit->Kode,
            'Nama' => $unit->Nama,
            'Jenis' => $unit->Jenis,
            'Email' => $unit->Email,
            'Telepon' => $unit->Telepon,
            'Status' => $unit->Status,
            'MengelolaAset' => $unit->MengelolaAset,
            'Urutan' => $unit->Urutan,
            'DibuatPada' => $unit->DibuatPada->toIso8601String(),
        ];
    }
}
