<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Actions;

use App\Domain\Platform\Infrastructure\Persistence\Models\NomorDokumen;

final class UbahNomorDokumen
{
    /**
     * @param array<string, mixed> $data
     */
    public function jalankan(NomorDokumen $nomorDokumen, array $data): NomorDokumen
    {
        // NomorTerakhir/PeriodeAktif sengaja tidak dapat diubah lewat sini --
        // hanya LayananNomorDokumen::berikutnya() yang boleh mengubah sequence.
        $nomorDokumen->fill($data);
        $nomorDokumen->save();

        return $nomorDokumen;
    }
}
