<?php

declare(strict_types=1);

namespace App\Domain\IntegrasiAudit\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PengirimanPanggilanBalikWebResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}
