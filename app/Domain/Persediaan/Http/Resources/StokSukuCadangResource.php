<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Http\Resources;

use App\Domain\Persediaan\Infrastructure\Persistence\Models\StokSukuCadang;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class StokSukuCadangResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var StokSukuCadang $stok */
        $stok = $this->resource;

        return [
            'Id' => $stok->Id,
            'GudangId' => $stok->GudangId,
            'NamaGudang' => $this->whenLoaded('gudang', fn () => $stok->gudang?->Nama),
            'LokasiGudangId' => $stok->LokasiGudangId,
            'NamaLokasiGudang' => $this->whenLoaded('lokasiGudang', fn () => $stok->lokasiGudang?->Nama),
            'SukuCadangId' => $stok->SukuCadangId,
            'NamaSukuCadang' => $this->whenLoaded('sukuCadang', fn () => $stok->sukuCadang?->Nama),
            'KodeSukuCadang' => $this->whenLoaded('sukuCadang', fn () => $stok->sukuCadang?->Kode),
            'SatuanDasar' => $this->whenLoaded('sukuCadang', fn () => $stok->sukuCadang?->SatuanDasar),
            'KelompokSukuCadangId' => $stok->KelompokSukuCadangId,
            'NomorBatch' => $this->whenLoaded('kelompokSukuCadang', fn () => $stok->kelompokSukuCadang?->NomorBatch),
            'JumlahTersedia' => $stok->JumlahTersedia,
            'JumlahDipesan' => $stok->JumlahDipesan,
            'JumlahDitahan' => $stok->JumlahDitahan,
            'JumlahTersediaBersih' => $stok->jumlahTersediaBersih(),
            'Versi' => $stok->Versi,
            'DiperbaruiPada' => $stok->DiperbaruiPada->toIso8601String(),
        ];
    }
}
