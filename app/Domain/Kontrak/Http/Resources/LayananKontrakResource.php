<?php

declare(strict_types=1);

namespace App\Domain\Kontrak\Http\Resources;

use App\Domain\Kontrak\Infrastructure\Persistence\Models\LayananKontrak;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class LayananKontrakResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var LayananKontrak $layanan */
        $layanan = $this->resource;

        return [
            'Id' => $layanan->Id,
            'KontrakId' => $layanan->KontrakId,
            'Nama' => $layanan->Nama,
            'Deskripsi' => $layanan->Deskripsi,
            'Kuota' => $layanan->Kuota,
            'Satuan' => $layanan->Satuan,
            'Terpakai' => $layanan->Terpakai,
            'DibuatPada' => $layanan->DibuatPada->toIso8601String(),
        ];
    }
}
