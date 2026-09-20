<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AturanTingkatLayananResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'Id' => $this->Id,
            'Prioritas' => $this->Prioritas,
            'MenitRespons' => $this->MenitRespons,
            'MenitPenyelesaian' => $this->MenitPenyelesaian,
            'MenghitungJamKerja' => $this->MenghitungJamKerja,
        ];
    }
}
