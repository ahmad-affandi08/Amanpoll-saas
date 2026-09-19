<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Actions;

use App\Domain\Platform\Infrastructure\Persistence\Models\NomorDokumen;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

final class HapusNomorDokumen
{
    public function jalankan(NomorDokumen $nomorDokumen): void
    {
        if ($nomorDokumen->NomorTerakhir > 0) {
            throw new AturanBisnisDilanggar('Pola nomor dokumen yang sudah pernah dipakai tidak dapat dihapus.');
        }

        $nomorDokumen->delete();
    }
}
