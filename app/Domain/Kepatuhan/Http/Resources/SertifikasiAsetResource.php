<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Http\Resources;

use App\Core\Organisasi\KalenderOrganisasi;
use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\SertifikasiAset;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SertifikasiAsetResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var SertifikasiAset $sertifikasi */
        $sertifikasi = $this->resource;

        return [
            'Id' => $sertifikasi->Id,
            'AsetId' => $sertifikasi->AsetId,
            'KodeAset' => $this->whenLoaded('aset', fn (): ?string => $sertifikasi->aset?->KodeAset),
            'NamaAset' => $this->whenLoaded('aset', fn (): ?string => $sertifikasi->aset?->Nama),
            'JenisSertifikasi' => $sertifikasi->JenisSertifikasi,
            'NomorSertifikat' => $sertifikasi->NomorSertifikat,
            'Penerbit' => $sertifikasi->Penerbit,
            'TerbitPada' => $sertifikasi->TerbitPada?->toDateString(),
            'BerlakuSampai' => $sertifikasi->BerlakuSampai?->toDateString(),
            'SisaHari' => $sertifikasi->BerlakuSampai === null
                ? null
                : (int) app(KalenderOrganisasi::class)->hariIni($sertifikasi->OrganisasiId)->diffInDays(CarbonImmutable::parse((string) $sertifikasi->BerlakuSampai), false),
            'Status' => $sertifikasi->Status,
            'BerkasId' => $sertifikasi->BerkasId,
            'DibuatPada' => $sertifikasi->DibuatPada->toIso8601String(),
        ];
    }
}
