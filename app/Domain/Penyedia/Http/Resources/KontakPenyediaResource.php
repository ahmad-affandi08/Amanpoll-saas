<?php

declare(strict_types=1);

namespace App\Domain\Penyedia\Http\Resources;

use App\Domain\Penyedia\Infrastructure\Persistence\Models\KontakPenyedia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class KontakPenyediaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var KontakPenyedia $kontakPenyedia */
        $kontakPenyedia = $this->resource;

        return [
            'Id' => $kontakPenyedia->Id,
            'PenyediaId' => $kontakPenyedia->PenyediaId,
            'Nama' => $kontakPenyedia->Nama,
            'Jabatan' => $kontakPenyedia->Jabatan,
            'Email' => $kontakPenyedia->Email,
            'Telepon' => $kontakPenyedia->Telepon,
            'Utama' => $kontakPenyedia->Utama,
            'DibuatPada' => $kontakPenyedia->DibuatPada->toIso8601String(),
        ];
    }
}
