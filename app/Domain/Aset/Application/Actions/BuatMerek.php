<?php

declare(strict_types=1);

namespace App\Domain\Aset\Application\Actions;

use App\Domain\Aset\Infrastructure\Persistence\Models\Merek;

final class BuatMerek
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function jalankan(array $data): Merek
    {
        return Merek::create($data);
    }
}
