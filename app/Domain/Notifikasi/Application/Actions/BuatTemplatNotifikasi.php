<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Application\Actions;

use App\Domain\Notifikasi\Infrastructure\Persistence\Models\TemplatNotifikasi;

final class BuatTemplatNotifikasi
{
    /**
     * @param array<string, mixed> $data
     */
    public function jalankan(array $data): TemplatNotifikasi
    {
        return TemplatNotifikasi::create($data);
    }
}
