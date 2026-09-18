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
            'JumlahIzin' => $this->whenCounted('peranIzin'),
            'JumlahPengguna' => $this->whenCounted('penggunaPeran'),
            'DibuatPada' => $peran->DibuatPada->toIso8601String(),
        ];
    }
}
