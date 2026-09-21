<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Resources;

use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PesananPembelian;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PesananPembelianResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var PesananPembelian $pesanan */
        $pesanan = $this->resource;

        return [
            'Id' => $pesanan->Id,
            'Nomor' => $pesanan->Nomor,
            'PenyediaId' => $pesanan->PenyediaId,
            'NamaPenyedia' => $this->whenLoaded('penyedia', fn (): ?string => $pesanan->penyedia?->Nama),
            'PermintaanPembelianId' => $pesanan->PermintaanPembelianId,
            'NomorPermintaanPembelian' => $this->whenLoaded('permintaanPembelian', fn (): ?string => $pesanan->permintaanPembelian?->Nomor),
            'PenawaranPenyediaId' => $pesanan->PenawaranPenyediaId,
            'PosAnggaranId' => $pesanan->PosAnggaranId,
            'NamaPosAnggaran' => $this->whenLoaded('posAnggaran', fn (): ?string => $pesanan->posAnggaran?->Nama),
            'TanggalPesanan' => $pesanan->TanggalPesanan->toDateString(),
            'TanggalKirimRencana' => $pesanan->TanggalKirimRencana?->toDateString(),
            'MataUang' => $pesanan->MataUang,
            'Subtotal' => $pesanan->Subtotal,
            'Pajak' => $pesanan->Pajak,
            'Diskon' => $pesanan->Diskon,
            'Total' => $pesanan->Total,
            'Status' => $pesanan->Status,
            'Catatan' => $pesanan->Catatan,
            'NamaPembuat' => $this->whenLoaded('dibuatOleh', fn (): ?string => $pesanan->dibuatOleh?->Nama),
            'JumlahPenerimaan' => $this->whenCounted('penerimaan'),
            'Detail' => DetailPesananPembelianResource::collection($this->whenLoaded('detail')),
            'Penerimaan' => PenerimaanPembelianResource::collection($this->whenLoaded('penerimaan')),
            'Tagihan' => TagihanPenyediaResource::collection($this->whenLoaded('tagihan')),
            'DibuatPada' => $pesanan->DibuatPada->toIso8601String(),
            'DiperbaruiPada' => $pesanan->DiperbaruiPada->toIso8601String(),
        ];
    }
}
