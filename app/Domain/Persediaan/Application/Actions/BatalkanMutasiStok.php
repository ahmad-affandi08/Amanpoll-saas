<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\MutasiStok;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

final class BatalkanMutasiStok
{
    public function __construct(private readonly LayananAudit $layananAudit) {}

    public function jalankan(MutasiStok $mutasiStok): MutasiStok
    {
        if ($mutasiStok->Status !== MutasiStok::STATUS_DRAFT) {
            throw new AturanBisnisDilanggar('Hanya mutasi berstatus draft yang bisa dibatalkan. Mutasi yang sudah diposting mengubah saldo stok dan tidak dapat dibatalkan begitu saja.');
        }

        $mutasiStok->Status = MutasiStok::STATUS_DIBATALKAN;
        $mutasiStok->save();

        $this->layananAudit->catat(
            aksi: 'MutasiStok.Dibatalkan',
            jenisEntitas: 'MutasiStok',
            entitasId: $mutasiStok->Id,
            dataSesudah: ['Status' => $mutasiStok->Status],
        );

        return $mutasiStok;
    }
}
