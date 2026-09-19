<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Platform\Infrastructure\Persistence\Models\KunciApi;

final class CabutKunciApi
{
    public function __construct(private readonly LayananAudit $layananAudit) {}

    public function jalankan(KunciApi $kunciApi): void
    {
        $statusSebelum = $kunciApi->Status;

        $kunciApi->Status = 'Dicabut';
        $kunciApi->save();

        $this->layananAudit->catat(
            aksi: 'KunciApi.Dicabut',
            jenisEntitas: 'KunciApi',
            entitasId: $kunciApi->Id,
            dataSebelum: ['Status' => $statusSebelum],
            dataSesudah: ['Status' => $kunciApi->Status],
        );
    }
}
