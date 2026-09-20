<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Application\Actions;

use App\Domain\Persediaan\Infrastructure\Persistence\Models\KelompokSukuCadang;

final class BuatKelompokSukuCadang
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function jalankan(array $data): KelompokSukuCadang
    {
        return KelompokSukuCadang::create($data);
    }
}
