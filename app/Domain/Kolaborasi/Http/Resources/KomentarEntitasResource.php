<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Http\Resources;

use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\KomentarEntitas;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class KomentarEntitasResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var KomentarEntitas $komentar */
        $komentar = $this->resource;
        /** @var Pengguna|null $pembuat */
        $pembuat = $komentar->dibuatOleh;

        return [
            'Id' => $komentar->Id,
            'JenisEntitas' => $komentar->JenisEntitas,
            'EntitasId' => $komentar->EntitasId,
            'IndukKomentarId' => $komentar->IndukKomentarId,
            'Isi' => $komentar->Isi,
            'DibuatOleh' => $komentar->DibuatOleh,
            'NamaPembuat' => $this->whenLoaded('dibuatOleh', fn () => $pembuat?->Nama),
            'DibuatPada' => $komentar->DibuatPada->toIso8601String(),
            'DiperbaruiPada' => $komentar->DiperbaruiPada->toIso8601String(),
        ];
    }
}
