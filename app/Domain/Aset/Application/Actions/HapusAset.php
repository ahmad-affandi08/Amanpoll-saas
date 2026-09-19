<?php

declare(strict_types=1);

namespace App\Domain\Aset\Application\Actions;

use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;

final class HapusAset
{
    public function jalankan(Aset $aset): void
    {
        $aset->delete();
    }
}
