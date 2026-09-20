<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\BiayaPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;

final class CatatBiayaPerintahKerja
{
    public function __construct(private readonly LayananAudit $audit) {}

    /** @param array<string, mixed> $data */
    public function jalankan(PerintahKerja $perintahKerja, array $data, string $penggunaId): BiayaPerintahKerja
    {
        $biaya = BiayaPerintahKerja::create([
            ...$data,
            'PerintahKerjaId' => $perintahKerja->Id,
            'DibuatOleh' => $penggunaId,
        ]);
        $this->audit->catat('CatatBiaya', 'PerintahKerja', $perintahKerja->Id, null, $biaya->toArray());

        return $biaya;
    }
}
