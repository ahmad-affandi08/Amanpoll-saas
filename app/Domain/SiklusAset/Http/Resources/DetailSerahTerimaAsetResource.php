<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Http\Resources;

use App\Domain\SiklusAset\Infrastructure\Persistence\Models\DetailSerahTerimaAset;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property DetailSerahTerimaAset $resource
 */
final class DetailSerahTerimaAsetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $detail = $this->resource;

        return [
            'Id' => $detail->Id,
            'AsetId' => $detail->AsetId,
            'NamaAset' => $this->whenLoaded('aset', fn () => $detail->aset?->Nama),
            'KodeAset' => $this->whenLoaded('aset', fn () => $detail->aset?->KodeAset),
            'KondisiSaatDiserahkan' => $detail->KondisiSaatDiserahkan,
            'KondisiSaatDiterima' => $detail->KondisiSaatDiterima,
            'Catatan' => $detail->Catatan,
            'DibuatPada' => $detail->DibuatPada,
        ];
    }
}
