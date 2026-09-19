<?php

declare(strict_types=1);

namespace App\Domain\Penyedia\Application\Actions;

use App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia;

final class LepaskanKategoriDariPenyedia
{
    public function jalankan(Penyedia $penyedia, string $kategoriPenyediaId): void
    {
        $penyedia->penyediaKategori()->where('KategoriPenyediaId', $kategoriPenyediaId)->delete();
    }
}
