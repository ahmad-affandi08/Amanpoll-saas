<?php

declare(strict_types=1);

namespace App\Domain\Aset\Application\Actions;

use App\Domain\Aset\Infrastructure\Persistence\Models\ModelAset;

final class BuatModelAset
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function jalankan(array $data): ModelAset
    {
        return ModelAset::create($data);
    }
}
