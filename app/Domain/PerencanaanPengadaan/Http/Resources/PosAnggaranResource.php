<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Resources;

use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PosAnggaran;
use App\Shared\Domain\ValueObjects\Uang;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PosAnggaranResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var PosAnggaran $pos */
        $pos = $this->resource;
        $sisa = Uang::dariString((string) $pos->Jumlah)
            ->kurang(Uang::dariString((string) $pos->Terpakai))
            ->kurang(Uang::dariString((string) $pos->Ditahan));

        return [
            'Id' => $pos->Id,
            'AnggaranId' => $pos->AnggaranId,
            'IndukId' => $pos->IndukId,
            'Kode' => $pos->Kode,
            'Nama' => $pos->Nama,
            'Jumlah' => $pos->Jumlah,
            'Terpakai' => $pos->Terpakai,
            'Ditahan' => $pos->Ditahan,
            'Sisa' => $sisa->keString(),
            'NamaInduk' => $this->whenLoaded('induk', fn (): ?string => $pos->induk?->Nama),
            'Anak' => PosAnggaranResource::collection($this->whenLoaded('anak')),
            'Transaksi' => TransaksiAnggaranResource::collection($this->whenLoaded('transaksi')),
        ];
    }
}
