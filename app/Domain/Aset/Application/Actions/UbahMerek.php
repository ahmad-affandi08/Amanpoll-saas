<?php

declare(strict_types=1);

namespace App\Domain\Aset\Application\Actions;

use App\Domain\Aset\Infrastructure\Persistence\Models\Merek;

final class UbahMerek
{
    /**
     * @param array<string, mixed> $data
     */
    public function jalankan(Merek $merek, array $data): Merek
    {
        $merek->fill($data);
        $merek->save();

        return $merek;
    }
}
