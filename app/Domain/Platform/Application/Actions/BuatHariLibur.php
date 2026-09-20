<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Actions;

use App\Domain\Platform\Infrastructure\Persistence\Models\HariLibur;

final class BuatHariLibur
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function jalankan(array $data): HariLibur
    {
        return HariLibur::create($data);
    }
}
