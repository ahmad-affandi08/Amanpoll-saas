<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Http\Resources;

use App\Domain\Persediaan\Infrastructure\Persistence\Models\MutasiStok;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class MutasiStokResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var MutasiStok $mutasiStok */
        $mutasiStok = $this->resource;

        return [
            'Id' => $mutasiStok->Id,
            'Nomor' => $mutasiStok->Nomor,
            'Jenis' => $mutasiStok->Jenis,
            'GudangAsalId' => $mutasiStok->GudangAsalId,
            'NamaGudangAsal' => $this->whenLoaded('gudangAsal', fn () => $mutasiStok->gudangAsal?->Nama),
            'GudangTujuanId' => $mutasiStok->GudangTujuanId,
            'NamaGudangTujuan' => $this->whenLoaded('gudangTujuan', fn () => $mutasiStok->gudangTujuan?->Nama),
            'ReferensiJenis' => $mutasiStok->ReferensiJenis,
            'ReferensiId' => $mutasiStok->ReferensiId,
            'Tanggal' => $mutasiStok->Tanggal->toIso8601String(),
            'Status' => $mutasiStok->Status,
            'Catatan' => $mutasiStok->Catatan,
            'NamaDibuatOleh' => $this->whenLoaded('dibuatOleh', fn () => $mutasiStok->dibuatOleh?->Nama),
            'DetailMutasiStok' => DetailMutasiStokResource::collection($this->whenLoaded('detailMutasiStok')),
            'DibuatPada' => $mutasiStok->DibuatPada->toIso8601String(),
        ];
    }
}
