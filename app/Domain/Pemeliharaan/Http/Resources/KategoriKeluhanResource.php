<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Http\Resources;

use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KategoriKeluhan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class KategoriKeluhanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $kategori = $this->resource instanceof KategoriKeluhan ? $this->resource : null;

        return [
            'Id' => $this->Id,
            'IndukId' => $this->IndukId,
            'Kode' => $this->Kode,
            'Nama' => $this->Nama,
            'TingkatLayananId' => $this->TingkatLayananId,
            'PrioritasBawaan' => $this->PrioritasBawaan,
            'AsetWajib' => $this->AsetWajib,
            'PeranPenanggungJawabId' => $this->PeranPenanggungJawabId,
            'UnitPengelolaId' => $kategori?->UnitPengelolaId,
            'Aktif' => $this->Aktif,
            'NamaInduk' => $this->whenLoaded('induk', fn () => $this->induk?->Nama),
            'NamaTingkatLayanan' => $this->whenLoaded('tingkatLayanan', fn () => $this->tingkatLayanan?->Nama),
            'NamaPeranPenanggungJawab' => $this->whenLoaded('peranPenanggungJawab', fn () => $this->peranPenanggungJawab?->Nama),
            'UnitPengelola' => $this->whenLoaded('unitPengelola', function () use ($kategori): ?array {
                $unit = $kategori?->unitPengelola;

                return $unit === null ? null : ['Id' => $unit->Id, 'Kode' => $unit->Kode, 'Nama' => $unit->Nama];
            }),
        ];
    }
}
