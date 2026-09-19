<?php

declare(strict_types=1);

namespace App\Domain\Aset\Application\Actions;

use App\Domain\Aset\Infrastructure\Persistence\Models\GaransiAset;

final class HapusGaransiAset
{
    public function jalankan(GaransiAset $garansiAset): void
    {
        $garansiAset->delete();
    }
}
