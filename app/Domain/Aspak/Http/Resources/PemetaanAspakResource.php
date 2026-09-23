<?php

declare(strict_types=1);

namespace App\Domain\Aspak\Http\Resources;

use App\Domain\Aspak\Infrastructure\Persistence\Models\PemetaanAspak;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PemetaanAspak */
final class PemetaanAspakResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'Id' => $this->Id,
            'AlkesAspakId' => $this->AlkesAspakId,
            'KodeAlkes' => $this->alkes?->Kode,
            'NamaAlkes' => $this->alkes?->Nama,
            'KategoriAsetId' => $this->KategoriAsetId,
            'NamaKategoriAset' => $this->kategoriAset?->Nama,
            'ModelAsetId' => $this->ModelAsetId,
            'NamaModelAset' => $this->modelAset?->Nama,
        ];
    }
}
