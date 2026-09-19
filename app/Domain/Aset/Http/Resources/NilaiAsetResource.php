<?php

declare(strict_types=1);

namespace App\Domain\Aset\Http\Resources;

use App\Domain\Aset\Infrastructure\Persistence\Models\NilaiAset;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class NilaiAsetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var NilaiAset $nilai */
        $nilai = $this->resource;

        return [
            'Id' => $nilai->Id,
            'AsetId' => $nilai->AsetId,
            'TanggalNilai' => $nilai->TanggalNilai->toDateString(),
            'NilaiBuku' => $nilai->NilaiBuku,
            'AkumulasiPenyusutan' => $nilai->AkumulasiPenyusutan,
            'BebanPenyusutanPeriode' => $nilai->BebanPenyusutanPeriode,
            'Metode' => $nilai->Metode,
            'DibuatPada' => $nilai->DibuatPada->toIso8601String(),
        ];
    }
}
