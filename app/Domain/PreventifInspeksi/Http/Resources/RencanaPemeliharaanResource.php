<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class RencanaPemeliharaanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}
