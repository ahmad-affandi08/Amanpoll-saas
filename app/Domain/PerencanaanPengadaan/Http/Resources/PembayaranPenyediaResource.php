<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Resources;

use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PembayaranPenyedia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PembayaranPenyediaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var PembayaranPenyedia $pembayaran */
        $pembayaran = $this->resource;

        return [
            'Id' => $pembayaran->Id,
            'TagihanPenyediaId' => $pembayaran->TagihanPenyediaId,
            'NomorPembayaran' => $pembayaran->NomorPembayaran,
            'TanggalBayar' => $pembayaran->TanggalBayar->toDateString(),
            'Jumlah' => $pembayaran->Jumlah,
            'Metode' => $pembayaran->Metode,
            'Referensi' => $pembayaran->Referensi,
            'NamaPembuat' => $this->whenLoaded('dibuatOleh', fn (): ?string => $pembayaran->dibuatOleh?->Nama),
            'DibuatPada' => $pembayaran->DibuatPada->toIso8601String(),
        ];
    }
}
