<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Http\Resources;

use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\DefinisiKolomKustom;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class DefinisiKolomKustomResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var DefinisiKolomKustom $definisi */
        $definisi = $this->resource;

        return [
            'Id' => $definisi->Id,
            'JenisEntitas' => $definisi->JenisEntitas,
            'Kode' => $definisi->Kode,
            'Label' => $definisi->Label,
            'TipeData' => $definisi->TipeData,
            'Wajib' => $definisi->Wajib,
            'Pilihan' => $definisi->Pilihan,
            'AturanValidasi' => $definisi->AturanValidasi,
            'NilaiBawaan' => $definisi->NilaiBawaan,
            'Urutan' => $definisi->Urutan,
            'Aktif' => $definisi->Aktif,
            'DibuatPada' => $definisi->DibuatPada->toIso8601String(),
        ];
    }
}
