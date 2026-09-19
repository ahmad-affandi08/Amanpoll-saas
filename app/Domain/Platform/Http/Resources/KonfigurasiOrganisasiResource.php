<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property array{Kunci: string, Namespace: string, Tipe: string, Label: string, Rahasia: bool, Nilai: mixed} $resource
 */
final class KonfigurasiOrganisasiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'Kunci' => $this->resource['Kunci'],
            'Namespace' => $this->resource['Namespace'],
            'Tipe' => $this->resource['Tipe'],
            'Label' => $this->resource['Label'],
            'Rahasia' => $this->resource['Rahasia'],
            'Nilai' => $this->resource['Nilai'],
        ];
    }
}
