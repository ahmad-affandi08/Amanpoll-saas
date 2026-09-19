<?php

declare(strict_types=1);

namespace App\Domain\Aset\Application\Actions;

use App\Domain\Aset\Infrastructure\Persistence\Models\RelasiAset;

final class HapusRelasiAset
{
    public function jalankan(RelasiAset $relasiAset): void
    {
        $relasiAset->delete();
    }
}
