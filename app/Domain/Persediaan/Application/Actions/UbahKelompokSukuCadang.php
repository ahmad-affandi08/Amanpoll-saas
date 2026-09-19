<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Application\Actions;

use App\Domain\Persediaan\Infrastructure\Persistence\Models\KelompokSukuCadang;

final class UbahKelompokSukuCadang
{
    /**
     * @param array<string, mixed> $data
     */
    public function jalankan(KelompokSukuCadang $kelompokSukuCadang, array $data): KelompokSukuCadang
    {
        $kelompokSukuCadang->fill($data);
        $kelompokSukuCadang->save();

        return $kelompokSukuCadang;
    }
}
