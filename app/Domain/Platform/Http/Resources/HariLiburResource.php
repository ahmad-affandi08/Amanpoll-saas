<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Resources;

use App\Domain\Platform\Infrastructure\Persistence\Models\HariLibur;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class HariLiburResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var HariLibur $hariLibur */
        $hariLibur = $this->resource;

        return [
            'Id' => $hariLibur->Id,
            'LokasiId' => $hariLibur->LokasiId,
            'Tanggal' => $hariLibur->Tanggal->toDateString(),
            'Nama' => $hariLibur->Nama,
            'BerulangTahunan' => $hariLibur->BerulangTahunan,
        ];
    }
}
