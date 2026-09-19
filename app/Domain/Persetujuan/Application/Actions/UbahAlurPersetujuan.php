<?php

declare(strict_types=1);

namespace App\Domain\Persetujuan\Application\Actions;

use App\Domain\Persetujuan\Infrastructure\Persistence\Models\AlurPersetujuan;

final class UbahAlurPersetujuan
{
    /**
     * @param array<string, mixed> $data
     */
    public function jalankan(AlurPersetujuan $alurPersetujuan, array $data): AlurPersetujuan
    {
        $alurPersetujuan->fill($data);
        $alurPersetujuan->save();

        return $alurPersetujuan;
    }
}
