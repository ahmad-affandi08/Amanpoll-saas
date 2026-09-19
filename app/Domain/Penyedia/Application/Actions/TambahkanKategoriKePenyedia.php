<?php

declare(strict_types=1);

namespace App\Domain\Penyedia\Application\Actions;

use App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\PenyediaKategori;

final class TambahkanKategoriKePenyedia
{
    public function jalankan(Penyedia $penyedia, string $kategoriPenyediaId): PenyediaKategori
    {
        $sudahAda = PenyediaKategori::query()
            ->where('PenyediaId', $penyedia->Id)
            ->where('KategoriPenyediaId', $kategoriPenyediaId)
            ->first();

        if ($sudahAda) {
            return $sudahAda;
        }

        return PenyediaKategori::create([
            'PenyediaId' => $penyedia->Id,
            'KategoriPenyediaId' => $kategoriPenyediaId,
        ]);
    }
}
