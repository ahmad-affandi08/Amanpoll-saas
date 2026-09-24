<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Resources;

use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PeranResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Peran $peran */
        $peran = $this->resource;

        return [
            'Id' => $peran->Id,
            'Kode' => $peran->Kode,
            'Nama' => $peran->Nama,
            'Keterangan' => $peran->Keterangan,
            'BawaanSistem' => (bool) $peran->BawaanSistem,
            'TampilanLapangan' => $peran->TampilanLapangan?->value,
            'JumlahIzin' => $this->whenCounted('peranIzin'),
            'JumlahPengguna' => $this->whenCounted('penggunaPeran'),
            'DaftarIzinId' => $this->whenLoaded('peranIzin', fn () => $peran->peranIzin->pluck('IzinId')->values()),
            'DibuatPada' => $peran->DibuatPada->toIso8601String(),
        ];
    }
}
