<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Actions;

use App\Domain\Platform\Infrastructure\Persistence\Models\NomorDokumen;

final class BuatNomorDokumen
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function jalankan(array $data): NomorDokumen
    {
        return NomorDokumen::create([...$data, 'NomorTerakhir' => 0, 'PeriodeAktif' => null]);
    }
}
