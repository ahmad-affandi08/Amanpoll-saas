<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Resources;

use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\TransaksiAnggaran;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TransaksiAnggaranResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var TransaksiAnggaran $transaksi */
        $transaksi = $this->resource;

        return [
            'Id' => $transaksi->Id,
            'PosAnggaranId' => $transaksi->PosAnggaranId,
            'NamaPosAnggaran' => $this->whenLoaded('posAnggaran', fn (): string => $transaksi->posAnggaran->Nama),
            'Jenis' => $transaksi->Jenis,
            'ReferensiJenis' => $transaksi->ReferensiJenis,
            'ReferensiId' => $transaksi->ReferensiId,
            'Jumlah' => $transaksi->Jumlah,
            'Tanggal' => $transaksi->Tanggal->toDateString(),
            'Keterangan' => $transaksi->Keterangan,
            'DibuatPada' => $transaksi->DibuatPada->toIso8601String(),
        ];
    }
}
