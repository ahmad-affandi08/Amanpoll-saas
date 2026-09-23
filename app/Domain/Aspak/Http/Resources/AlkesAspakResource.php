<?php

declare(strict_types=1);

namespace App\Domain\Aspak\Http\Resources;

use App\Domain\Aspak\Infrastructure\Persistence\Models\AlkesAspak;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AlkesAspak */
final class AlkesAspakResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'Id' => $this->Id,
            'Kode' => $this->Kode,
            'Nama' => $this->Nama,
            'Kelompok' => $this->Kelompok,
            'Satuan' => $this->Satuan,
            'Aktif' => (bool) $this->Aktif,
            'JumlahPemetaan' => (int) ($this->pemetaan_count ?? 0),
        ];
    }
}
