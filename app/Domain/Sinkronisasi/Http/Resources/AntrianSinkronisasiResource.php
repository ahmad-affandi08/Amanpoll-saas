<?php

declare(strict_types=1);

namespace App\Domain\Sinkronisasi\Http\Resources;

use App\Domain\Sinkronisasi\Infrastructure\Persistence\Models\AntrianSinkronisasi;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Status satu mutasi offline untuk klien PWA. Muatan aslinya tidak dikirim
 * balik: klien masih menyimpannya sendiri dan server tidak perlu menggandakan
 * data lapangan di jalur respons.
 *
 * @mixin AntrianSinkronisasi
 */
final class AntrianSinkronisasiResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'Id' => $this->Id,
            'KunciOperasi' => $this->KunciOperasi,
            'Operasi' => $this->Operasi,
            'JenisEntitas' => $this->JenisEntitas,
            'EntitasId' => $this->EntitasId,
            'VersiKlien' => $this->VersiKlien,
            'Status' => $this->Status,
            'Konflik' => $this->Konflik,
            'Percobaan' => $this->Percobaan,
            'DiterimaPada' => $this->DiterimaPada->toIso8601String(),
            'DiprosesPada' => $this->DiprosesPada?->toIso8601String(),
        ];
    }
}
