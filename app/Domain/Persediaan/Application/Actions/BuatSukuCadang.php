<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Application\Actions;

use App\Domain\Persediaan\Infrastructure\Persistence\Models\SukuCadang;

final class BuatSukuCadang
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function jalankan(array $data): SukuCadang
    {
        return SukuCadang::create($data);
    }
}
