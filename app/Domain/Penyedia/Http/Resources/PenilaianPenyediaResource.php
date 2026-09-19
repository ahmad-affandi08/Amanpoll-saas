<?php

declare(strict_types=1);

namespace App\Domain\Penyedia\Http\Resources;

use App\Domain\Penyedia\Infrastructure\Persistence\Models\PenilaianPenyedia;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PenilaianPenyediaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var PenilaianPenyedia $penilaian */
        $penilaian = $this->resource;
        /** @var Pengguna|null $penilai */
        $penilai = $this->whenLoaded('dinilaiOleh') ? $penilaian->dinilaiOleh : null;

        return [
            'Id' => $penilaian->Id,
            'PenyediaId' => $penilaian->PenyediaId,
            'PeriodeMulai' => $penilaian->PeriodeMulai->toDateString(),
            'PeriodeSelesai' => $penilaian->PeriodeSelesai->toDateString(),
            'SkorKualitas' => $penilaian->SkorKualitas,
            'SkorKetepatanWaktu' => $penilaian->SkorKetepatanWaktu,
            'SkorHarga' => $penilaian->SkorHarga,
            'SkorLayanan' => $penilaian->SkorLayanan,
            'SkorTotal' => $penilaian->SkorTotal,
            'Catatan' => $penilaian->Catatan,
            'NamaPenilai' => $penilai?->Nama,
            'DibuatPada' => $penilaian->DibuatPada->toIso8601String(),
        ];
    }
}
