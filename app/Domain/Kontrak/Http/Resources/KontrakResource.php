<?php

declare(strict_types=1);

namespace App\Domain\Kontrak\Http\Resources;

use App\Domain\Kontrak\Infrastructure\Persistence\Models\Kontrak;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class KontrakResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Kontrak $kontrak */
        $kontrak = $this->resource;

        return [
            'Id' => $kontrak->Id,
            'Nomor' => $kontrak->Nomor,
            'Nama' => $kontrak->Nama,
            'Jenis' => $kontrak->Jenis,
            'PenyediaId' => $kontrak->PenyediaId,
            'NamaPenyedia' => $this->whenLoaded('penyedia', fn (): ?string => $kontrak->penyedia?->Nama),
            'MulaiPada' => $kontrak->MulaiPada->toDateString(),
            'BerakhirPada' => $kontrak->BerakhirPada->toDateString(),
            'SisaHari' => (int) CarbonImmutable::today()->diffInDays(CarbonImmutable::parse((string) $kontrak->BerakhirPada), false),
            'Nilai' => $kontrak->Nilai,
            'MataUang' => $kontrak->MataUang,
            'TingkatLayananId' => $kontrak->TingkatLayananId,
            'NamaTingkatLayanan' => $this->whenLoaded('tingkatLayanan', fn (): ?string => $kontrak->tingkatLayanan?->Nama),
            'PeringatanHariSebelum' => $kontrak->PeringatanHariSebelum,
            'Status' => $kontrak->Status,
            'Catatan' => $kontrak->Catatan,
            'JumlahAset' => $this->whenCounted('kontrakAset'),
            'JumlahLayanan' => $this->whenCounted('layanan'),
            'Aset' => KontrakAsetResource::collection($this->whenLoaded('kontrakAset')),
            'Layanan' => LayananKontrakResource::collection($this->whenLoaded('layanan')),
            'DibuatPada' => $kontrak->DibuatPada->toIso8601String(),
            'DiperbaruiPada' => $kontrak->DiperbaruiPada->toIso8601String(),
        ];
    }
}
