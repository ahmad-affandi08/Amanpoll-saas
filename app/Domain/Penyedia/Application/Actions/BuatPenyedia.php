<?php

declare(strict_types=1);

namespace App\Domain\Penyedia\Application\Actions;

use App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia;

final class BuatPenyedia
{
    /**
     * @param array<string, mixed> $data
     */
    public function jalankan(array $data): Penyedia
    {
        return Penyedia::create($data);
    }
}
