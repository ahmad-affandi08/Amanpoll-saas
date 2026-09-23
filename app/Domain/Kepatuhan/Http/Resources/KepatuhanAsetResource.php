<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Http\Resources;

use App\Core\Organisasi\KalenderOrganisasi;
use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\KepatuhanAset;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class KepatuhanAsetResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var KepatuhanAset $kepatuhan */
        $kepatuhan = $this->resource;

        return [
            'Id' => $kepatuhan->Id,
            'AsetId' => $kepatuhan->AsetId,
            'KodeAset' => $this->whenLoaded('aset', fn (): ?string => $kepatuhan->aset?->KodeAset),
            'NamaAset' => $this->whenLoaded('aset', fn (): ?string => $kepatuhan->aset?->Nama),
            'PersyaratanKepatuhanId' => $kepatuhan->PersyaratanKepatuhanId,
            'KodePersyaratan' => $this->whenLoaded('persyaratanKepatuhan', fn (): ?string => $kepatuhan->persyaratanKepatuhan?->Kode),
            'NamaPersyaratan' => $this->whenLoaded('persyaratanKepatuhan', fn (): ?string => $kepatuhan->persyaratanKepatuhan?->Nama),
            'BuktiYangDiperlukan' => $this->whenLoaded('persyaratanKepatuhan', fn (): ?string => $kepatuhan->persyaratanKepatuhan?->BuktiYangDiperlukan),
            'Status' => $kepatuhan->Status,
            'TanggalPemeriksaan' => $kepatuhan->TanggalPemeriksaan?->toDateString(),
            'BerlakuSampai' => $kepatuhan->BerlakuSampai?->toDateString(),
            'SisaHari' => $kepatuhan->BerlakuSampai === null
                ? null
                : (int) app(KalenderOrganisasi::class)->hariIni($kepatuhan->OrganisasiId)->diffInDays(CarbonImmutable::parse((string) $kepatuhan->BerlakuSampai), false),
            'Catatan' => $kepatuhan->Catatan,
            'NamaPemeriksa' => $this->whenLoaded('diperiksaOleh', fn (): ?string => $kepatuhan->diperiksaOleh?->Nama),
            'DiperbaruiPada' => $kepatuhan->DiperbaruiPada->toIso8601String(),
        ];
    }
}
