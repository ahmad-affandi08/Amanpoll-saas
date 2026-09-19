<?php

declare(strict_types=1);

namespace App\Domain\Persetujuan\Application\Actions;

use App\Domain\Persetujuan\Infrastructure\Persistence\Models\AlurPersetujuan;

final class BuatAlurPersetujuan
{
    /**
     * @param array<string, mixed> $data
     */
    public function jalankan(array $data): AlurPersetujuan
    {
        // Alur baru selalu tidak aktif -- harus punya minimal satu tahap
        // dulu sebelum bisa diaktifkan (lihat UbahAlurPersetujuan).
        $data['Aktif'] = false;

        return AlurPersetujuan::create($data);
    }
}
