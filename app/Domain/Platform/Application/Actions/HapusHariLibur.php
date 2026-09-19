<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Actions;

use App\Domain\Platform\Infrastructure\Persistence\Models\HariLibur;

final class HapusHariLibur
{
    public function jalankan(HariLibur $hariLibur): void
    {
        $hariLibur->delete();
    }
}
