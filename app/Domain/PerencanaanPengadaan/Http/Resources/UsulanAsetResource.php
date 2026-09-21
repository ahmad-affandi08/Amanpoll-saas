<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Resources;

use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\UsulanAset;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class UsulanAsetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var UsulanAset $usulan */
        $usulan = $this->resource;

        return [
            'Id' => $usulan->Id,
            'Nomor' => $usulan->Nomor,
            'UnitOrganisasiId' => $usulan->UnitOrganisasiId,
            'NamaUnitOrganisasi' => $this->whenLoaded('unitOrganisasi', fn (): string => $usulan->unitOrganisasi->Nama),
            'KategoriAsetId' => $usulan->KategoriAsetId,
            'NamaKategoriAset' => $this->whenLoaded('kategoriAset', fn (): ?string => $usulan->kategoriAset?->Nama),
            'ModelAsetId' => $usulan->ModelAsetId,
            'NamaModelAset' => $this->whenLoaded('modelAset', fn (): ?string => $usulan->modelAset?->Nama),
            'NamaKebutuhan' => $usulan->NamaKebutuhan,
            'Jumlah' => $usulan->Jumlah,
            'EstimasiHargaSatuan' => $usulan->EstimasiHargaSatuan,
            'Alasan' => $usulan->Alasan,
            'JenisKebutuhan' => $usulan->JenisKebutuhan,
            'TahunKebutuhan' => $usulan->TahunKebutuhan,
            'Prioritas' => $usulan->Prioritas,
            'Status' => $usulan->Status,
            'DiajukanOleh' => $usulan->DiajukanOleh,
            'NamaPengaju' => $this->whenLoaded('diajukanOleh', fn (): string => $usulan->diajukanOleh->Nama),
            'DiajukanPada' => $usulan->DiajukanPada?->toIso8601String(),
            'Penilaian' => PenilaianUsulanAsetResource::collection($this->whenLoaded('penilaian')),
            'DibuatPada' => $usulan->DibuatPada->toIso8601String(),
        ];
    }
}
