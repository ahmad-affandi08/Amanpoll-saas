<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Http\Resources;

use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\RiwayatStatusKeluhan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin RiwayatStatusKeluhan */
final class RiwayatStatusKeluhanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'Id' => $this->Id,
            'StatusSebelum' => $this->StatusSebelum,
            'StatusSesudah' => $this->StatusSesudah,
            'Catatan' => $this->Catatan,
            'NamaPengubah' => $this->whenLoaded('diubahOleh', fn () => $this->diubahOleh?->Nama),
            'DiubahPada' => $this->DiubahPada->toIso8601String(),
        ];
    }
}
