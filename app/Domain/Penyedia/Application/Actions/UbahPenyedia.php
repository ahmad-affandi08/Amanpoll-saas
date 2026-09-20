<?php

declare(strict_types=1);

namespace App\Domain\Penyedia\Application\Actions;

use App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia;

final class UbahPenyedia
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function jalankan(Penyedia $penyedia, array $data): Penyedia
    {
        $penyedia->fill($data);
        $penyedia->save();

        return $penyedia;
    }
}
