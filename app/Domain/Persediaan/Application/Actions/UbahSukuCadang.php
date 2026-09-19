<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Application\Actions;

use App\Domain\Persediaan\Infrastructure\Persistence\Models\SukuCadang;

final class UbahSukuCadang
{
    /**
     * @param array<string, mixed> $data
     */
    public function jalankan(SukuCadang $sukuCadang, array $data): SukuCadang
    {
        $sukuCadang->fill($data);
        $sukuCadang->save();

        return $sukuCadang;
    }
}
