<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Resources;

use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\DetailPenawaranPenyedia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class DetailPenawaranPenyediaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var DetailPenawaranPenyedia $detail */
        $detail = $this->resource;

        return [
            'Id' => $detail->Id,
            'DetailPermintaanPembelianId' => $detail->DetailPermintaanPembelianId,
            'Deskripsi' => $detail->Deskripsi,
            'Jumlah' => $detail->Jumlah,
            'HargaSatuan' => $detail->HargaSatuan,
            'Diskon' => $detail->Diskon,
            'Pajak' => $detail->Pajak,
            'Total' => $detail->Total,
            'WaktuPengirimanHari' => $detail->WaktuPengirimanHari,
        ];
    }
}
