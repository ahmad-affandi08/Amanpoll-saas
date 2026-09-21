<?php

declare(strict_types=1);

namespace App\Domain\Kontrak\Http\Resources;

use App\Domain\Kontrak\Infrastructure\Persistence\Models\KontrakAset;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class KontrakAsetResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var KontrakAset $cakupan */
        $cakupan = $this->resource;

        return [
            'Id' => $cakupan->Id,
            'KontrakId' => $cakupan->KontrakId,
            'AsetId' => $cakupan->AsetId,
            'KodeAset' => $this->whenLoaded('aset', fn (): ?string => $cakupan->aset?->KodeAset),
            'NamaAset' => $this->whenLoaded('aset', fn (): ?string => $cakupan->aset?->Nama),
            'MulaiPada' => $cakupan->MulaiPada?->toDateString(),
            'BerakhirPada' => $cakupan->BerakhirPada?->toDateString(),
            'Catatan' => $cakupan->Catatan,
        ];
    }
}
