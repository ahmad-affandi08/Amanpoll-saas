<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Http\Resources;

use App\Domain\Persediaan\Infrastructure\Persistence\Models\SukuCadang;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SukuCadangResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var SukuCadang $sukuCadang */
        $sukuCadang = $this->resource;

        return [
            'Id' => $sukuCadang->Id,
            'KategoriSukuCadangId' => $sukuCadang->KategoriSukuCadangId,
            'NamaKategori' => $this->whenLoaded('kategoriSukuCadang', fn () => $sukuCadang->kategoriSukuCadang?->Nama),
            'Kode' => $sukuCadang->Kode,
            'Nama' => $sukuCadang->Nama,
            'NomorBagian' => $sukuCadang->NomorBagian,
            'KodeBatang' => $sukuCadang->KodeBatang,
            'SatuanDasar' => $sukuCadang->SatuanDasar,
            'StokMinimum' => $sukuCadang->StokMinimum,
            'StokMaksimum' => $sukuCadang->StokMaksimum,
            'TitikPesanUlang' => $sukuCadang->TitikPesanUlang,
            'HargaRataRata' => $sukuCadang->HargaRataRata,
            'MemakaiBatch' => $sukuCadang->MemakaiBatch,
            'MemakaiKadaluarsa' => $sukuCadang->MemakaiKadaluarsa,
            'Status' => $sukuCadang->Status,
            'JumlahTersediaBersih' => array_key_exists('JumlahTersediaBersih', $sukuCadang->getAttributes())
                ? (float) $sukuCadang->getAttribute('JumlahTersediaBersih') : null,
            'DibuatPada' => $sukuCadang->DibuatPada->toIso8601String(),
        ];
    }
}
