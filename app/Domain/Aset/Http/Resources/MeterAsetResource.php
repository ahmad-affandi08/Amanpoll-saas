<?php

declare(strict_types=1);

namespace App\Domain\Aset\Http\Resources;

use App\Domain\Aset\Infrastructure\Persistence\Models\MeterAset;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class MeterAsetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var MeterAset $meter */
        $meter = $this->resource;

        return [
            'Id' => $meter->Id,
            'AsetId' => $meter->AsetId,
            'Nama' => $meter->Nama,
            'Satuan' => $meter->Satuan,
            'Jenis' => $meter->Jenis,
            'NilaiAwal' => $meter->NilaiAwal,
            'Aktif' => $meter->Aktif,
            'NilaiTerakhir' => $this->whenLoaded('pembacaan', function () use ($meter) {
                $pembacaanTerakhir = $meter->pembacaan->first();

                return $pembacaanTerakhir === null ? $meter->NilaiAwal : $pembacaanTerakhir->Nilai;
            }),
            'DibuatPada' => $meter->DibuatPada->toIso8601String(),
        ];
    }
}
