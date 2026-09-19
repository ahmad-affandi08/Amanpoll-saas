<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Http\Resources;

use App\Domain\Notifikasi\Infrastructure\Persistence\Models\TemplatNotifikasi;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TemplatNotifikasiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var TemplatNotifikasi $templat */
        $templat = $this->resource;

        return [
            'Id' => $templat->Id,
            'Kode' => $templat->Kode,
            'Kanal' => $templat->Kanal,
            'JudulTemplat' => $templat->JudulTemplat,
            'IsiTemplat' => $templat->IsiTemplat,
            'Variabel' => $templat->Variabel,
            'Aktif' => $templat->Aktif,
            'DibuatPada' => $templat->DibuatPada->toIso8601String(),
        ];
    }
}
