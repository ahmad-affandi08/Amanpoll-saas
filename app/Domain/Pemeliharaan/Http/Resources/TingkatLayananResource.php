<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Http\Resources;

use App\Domain\Notifikasi\Http\Resources\EskalasiTingkatLayananResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TingkatLayananResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'Id' => $this->Id,
            'Kode' => $this->Kode,
            'Nama' => $this->Nama,
            'Deskripsi' => $this->Deskripsi,
            'HariKerja' => $this->HariKerja ?: [1, 2, 3, 4, 5],
            'JamKerjaMulai' => substr((string) $this->JamKerjaMulai, 0, 5),
            'JamKerjaSelesai' => substr((string) $this->JamKerjaSelesai, 0, 5),
            'MemperhitungkanHariLibur' => $this->MemperhitungkanHariLibur,
            'Aktif' => $this->Aktif,
            'Aturan' => AturanTingkatLayananResource::collection($this->whenLoaded('aturan')),
            'Eskalasi' => EskalasiTingkatLayananResource::collection($this->whenLoaded('eskalasi')),
        ];
    }
}
