<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Http\Resources;

use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\Berkas;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class BerkasResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Berkas $berkas */
        $berkas = $this->resource;

        return [
            'Id' => $berkas->Id,
            'NamaAsli' => $berkas->NamaAsli,
            'JenisMime' => $berkas->JenisMime,
            'UkuranByte' => $berkas->UkuranByte,
            'NamaUnduhan' => $berkas->namaUnduhan(),
            'MetodeKompresi' => $berkas->MetodeKompresi->value,
            'UkuranAsliByte' => $berkas->UkuranAsliByte,
            'UkuranTersimpanByte' => $berkas->UkuranTersimpanByte,
            'DiunggahOleh' => $berkas->DiunggahOleh,
            'DibuatPada' => $berkas->DibuatPada->toIso8601String(),
        ];
    }
}
