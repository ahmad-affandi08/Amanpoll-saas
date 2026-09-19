<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Application\Actions;

use App\Domain\Persediaan\Infrastructure\Persistence\Models\KompatibilitasSukuCadang;

final class HapusKompatibilitasSukuCadang
{
    public function jalankan(KompatibilitasSukuCadang $kompatibilitasSukuCadang): void
    {
        $kompatibilitasSukuCadang->delete();
    }
}
