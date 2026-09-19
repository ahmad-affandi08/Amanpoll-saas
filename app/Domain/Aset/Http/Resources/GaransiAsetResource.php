<?php

declare(strict_types=1);

namespace App\Domain\Aset\Http\Resources;

use App\Domain\Aset\Infrastructure\Persistence\Models\GaransiAset;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class GaransiAsetResource extends JsonResource
{
    private const AMBANG_PENGINGAT_HARI = 30;

    public function toArray(Request $request): array
    {
        /** @var GaransiAset $garansi */
        $garansi = $this->resource;
        $sisaHari = (int) now()->startOfDay()->diffInDays($garansi->BerakhirPada, false);

        return [
            'Id' => $garansi->Id,
            'AsetId' => $garansi->AsetId,
            'PenyediaId' => $garansi->PenyediaId,
            'NamaPenyedia' => $this->whenLoaded('penyedia', fn () => $garansi->penyedia?->Nama),
            'NomorGaransi' => $garansi->NomorGaransi,
            'JenisGaransi' => $garansi->JenisGaransi,
            'MulaiPada' => $garansi->MulaiPada->toDateString(),
            'BerakhirPada' => $garansi->BerakhirPada->toDateString(),
            'Cakupan' => $garansi->Cakupan,
            'Status' => $garansi->Status,
            'SisaHari' => $sisaHari,
            'AkanBerakhir' => $garansi->Status === GaransiAset::STATUS_AKTIF && $sisaHari >= 0 && $sisaHari <= self::AMBANG_PENGINGAT_HARI,
            'SudahBerakhir' => $garansi->Status === GaransiAset::STATUS_AKTIF && $sisaHari < 0,
            'DibuatPada' => $garansi->DibuatPada->toIso8601String(),
        ];
    }
}
