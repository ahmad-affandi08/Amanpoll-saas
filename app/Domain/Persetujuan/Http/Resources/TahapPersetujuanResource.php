<?php

declare(strict_types=1);

namespace App\Domain\Persetujuan\Http\Resources;

use App\Domain\Persetujuan\Infrastructure\Persistence\Models\TahapPersetujuan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TahapPersetujuanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var TahapPersetujuan $tahap */
        $tahap = $this->resource;
        /** @var Peran|null $peran */
        $peran = $tahap->peran;
        /** @var Pengguna|null $pengguna */
        $pengguna = $tahap->pengguna;

        return [
            'Id' => $tahap->Id,
            'AlurPersetujuanId' => $tahap->AlurPersetujuanId,
            'Urutan' => $tahap->Urutan,
            'Nama' => $tahap->Nama,
            'JenisPenyetuju' => $tahap->JenisPenyetuju,
            'PeranId' => $tahap->PeranId,
            'NamaPeran' => $this->whenLoaded('peran', fn () => $peran?->Nama),
            'PenggunaId' => $tahap->PenggunaId,
            'NamaPengguna' => $this->whenLoaded('pengguna', fn () => $pengguna?->Nama),
            'JumlahMinimumPenyetuju' => $tahap->JumlahMinimumPenyetuju,
            'BolehMenyetujuiSendiri' => $tahap->BolehMenyetujuiSendiri,
            'BatasWaktuMenit' => $tahap->BatasWaktuMenit,
        ];
    }
}
