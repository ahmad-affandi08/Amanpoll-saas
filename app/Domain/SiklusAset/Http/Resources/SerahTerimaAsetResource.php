<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \App\Domain\SiklusAset\Infrastructure\Persistence\Models\SerahTerimaAset $resource
 */
final class SerahTerimaAsetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $serahTerima = $this->resource;

        return [
            'Id' => $serahTerima->Id,
            'Nomor' => $serahTerima->Nomor,
            'PermintaanMutasiAsetId' => $serahTerima->PermintaanMutasiAsetId,
            'Jenis' => $serahTerima->Jenis,
            'PihakMenyerahkan' => $serahTerima->PihakMenyerahkan,
            'NamaPihakMenyerahkan' => $this->whenLoaded('pihakMenyerahkan', fn () => $serahTerima->pihakMenyerahkan?->Nama),
            'PihakMenerima' => $serahTerima->PihakMenerima,
            'NamaPihakMenerima' => $this->whenLoaded('pihakMenerima', fn () => $serahTerima->pihakMenerima?->Nama),
            'DiserahkanPada' => $serahTerima->DiserahkanPada,
            'DiterimaPada' => $serahTerima->DiterimaPada,
            'Status' => $serahTerima->Status,
            'Catatan' => $serahTerima->Catatan,
            'DetailSerahTerimaAset' => DetailSerahTerimaAsetResource::collection($this->whenLoaded('detailSerahTerimaAset')),
            'DibuatPada' => $serahTerima->DibuatPada,
        ];
    }
}
