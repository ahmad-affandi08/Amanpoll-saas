<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class KategoriKeluhanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'Id' => $this->Id,
            'IndukId' => $this->IndukId,
            'Kode' => $this->Kode,
            'Nama' => $this->Nama,
            'TingkatLayananId' => $this->TingkatLayananId,
            'PrioritasBawaan' => $this->PrioritasBawaan,
            'AsetWajib' => $this->AsetWajib,
            'PeranPenanggungJawabId' => $this->PeranPenanggungJawabId,
            'Aktif' => $this->Aktif,
            'NamaInduk' => $this->whenLoaded('induk', fn () => $this->induk?->Nama),
            'NamaTingkatLayanan' => $this->whenLoaded('tingkatLayanan', fn () => $this->tingkatLayanan?->Nama),
            'NamaPeranPenanggungJawab' => $this->whenLoaded('peranPenanggungJawab', fn () => $this->peranPenanggungJawab?->Nama),
        ];
    }
}
