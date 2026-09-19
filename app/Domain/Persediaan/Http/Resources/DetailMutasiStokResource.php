<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Http\Resources;

use App\Domain\Persediaan\Infrastructure\Persistence\Models\DetailMutasiStok;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class DetailMutasiStokResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var DetailMutasiStok $detail */
        $detail = $this->resource;

        return [
            'Id' => $detail->Id,
            'SukuCadangId' => $detail->SukuCadangId,
            'NamaSukuCadang' => $this->whenLoaded('sukuCadang', fn () => $detail->sukuCadang?->Nama),
            'KodeSukuCadang' => $this->whenLoaded('sukuCadang', fn () => $detail->sukuCadang?->Kode),
            'SatuanDasar' => $this->whenLoaded('sukuCadang', fn () => $detail->sukuCadang?->SatuanDasar),
            'KelompokSukuCadangId' => $detail->KelompokSukuCadangId,
            'NomorBatch' => $this->whenLoaded('kelompokSukuCadang', fn () => $detail->kelompokSukuCadang?->NomorBatch),
            'Jumlah' => $detail->Jumlah,
            'HargaSatuan' => $detail->HargaSatuan,
            'LokasiGudangAsalId' => $detail->LokasiGudangAsalId,
            'NamaLokasiGudangAsal' => $this->whenLoaded('lokasiGudangAsal', fn () => $detail->lokasiGudangAsal?->Nama),
            'LokasiGudangTujuanId' => $detail->LokasiGudangTujuanId,
            'NamaLokasiGudangTujuan' => $this->whenLoaded('lokasiGudangTujuan', fn () => $detail->lokasiGudangTujuan?->Nama),
            'DibuatPada' => $detail->DibuatPada->toIso8601String(),
        ];
    }
}
