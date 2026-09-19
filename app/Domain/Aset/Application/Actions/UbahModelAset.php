<?php

declare(strict_types=1);

namespace App\Domain\Aset\Application\Actions;

use App\Domain\Aset\Infrastructure\Persistence\Models\ModelAset;

final class UbahModelAset
{
    /**
     * @param array<string, mixed> $data
     */
    public function jalankan(ModelAset $modelAset, array $data): ModelAset
    {
        $modelAset->fill($data);
        $modelAset->save();

        return $modelAset;
    }
}
