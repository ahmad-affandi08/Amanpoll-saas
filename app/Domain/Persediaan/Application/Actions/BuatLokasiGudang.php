<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Application\Actions;

use App\Domain\Persediaan\Infrastructure\Persistence\Models\LokasiGudang;

final class BuatLokasiGudang
{
    /**
     * @param array<string, mixed> $data
     */
    public function jalankan(array $data): LokasiGudang
    {
        return LokasiGudang::create($data);
    }
}
