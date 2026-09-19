<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Actions;

use App\Domain\Platform\Infrastructure\Persistence\Models\HariLibur;

final class UbahHariLibur
{
    /**
     * @param array<string, mixed> $data
     */
    public function jalankan(HariLibur $hariLibur, array $data): HariLibur
    {
        $hariLibur->fill($data);
        $hariLibur->save();

        return $hariLibur;
    }
}
