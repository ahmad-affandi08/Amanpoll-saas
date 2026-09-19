<?php

declare(strict_types=1);

namespace App\Domain\Penyedia\Application\Actions;

use App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia;

final class HapusPenyedia
{
    public function jalankan(Penyedia $penyedia): void
    {
        $penyedia->delete();
    }
}
