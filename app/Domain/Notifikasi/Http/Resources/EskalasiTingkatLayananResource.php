<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class EskalasiTingkatLayananResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'Id' => $this->Id,
            'Tahap' => $this->Tahap,
            'Pemicu' => $this->Pemicu,
            'SetelahMenit' => $this->SetelahMenit,
            'PeranId' => $this->PeranId,
            'PenggunaId' => $this->PenggunaId,
            'Kanal' => $this->Kanal ?? ['InApp'],
            'Aktif' => $this->Aktif,
            'NamaPeran' => $this->whenLoaded('peran', fn () => $this->peran?->Nama),
            'NamaPengguna' => $this->whenLoaded('pengguna', fn () => $this->pengguna?->Nama),
        ];
    }
}
