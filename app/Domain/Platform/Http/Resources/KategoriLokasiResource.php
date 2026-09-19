<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Resources;

use App\Domain\Platform\Infrastructure\Persistence\Models\KategoriLokasi;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class KategoriLokasiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var KategoriLokasi $kategoriLokasi */
        $kategoriLokasi = $this->resource;

        return [
            'Id' => $kategoriLokasi->Id,
            'Kode' => $kategoriLokasi->Kode,
            'Nama' => $kategoriLokasi->Nama,
            'Keterangan' => $kategoriLokasi->Keterangan,
            'DibuatPada' => $kategoriLokasi->DibuatPada->toIso8601String(),
        ];
    }
}
