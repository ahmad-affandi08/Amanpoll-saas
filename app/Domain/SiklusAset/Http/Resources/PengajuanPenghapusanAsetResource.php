<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Http\Resources;

use App\Domain\SiklusAset\Infrastructure\Persistence\Models\PengajuanPenghapusanAset;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property PengajuanPenghapusanAset $resource
 */
final class PengajuanPenghapusanAsetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $pengajuan = $this->resource;

        return [
            'Id' => $pengajuan->Id,
            'Nomor' => $pengajuan->Nomor,
            'Alasan' => $pengajuan->Alasan,
            'MetodePenghapusan' => $pengajuan->MetodePenghapusan,
            'Status' => $pengajuan->Status,
            'DiajukanOleh' => $pengajuan->DiajukanOleh,
            'NamaDiajukanOleh' => $this->whenLoaded('diajukanOleh', fn () => $pengajuan->diajukanOleh?->Nama),
            'DiajukanPada' => $pengajuan->DiajukanPada,
            'DiselesaikanPada' => $pengajuan->DiselesaikanPada,
            'DetailPenghapusanAset' => DetailPenghapusanAsetResource::collection($this->whenLoaded('detailPenghapusanAset')),
            'DibuatPada' => $pengajuan->DibuatPada,
        ];
    }
}
