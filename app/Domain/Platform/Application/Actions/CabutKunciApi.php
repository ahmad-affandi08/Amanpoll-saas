<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Actions;

use App\Domain\Platform\Infrastructure\Persistence\Models\KunciApi;

final class CabutKunciApi
{
    public function jalankan(KunciApi $kunciApi): void
    {
        $kunciApi->Status = 'Dicabut';
        $kunciApi->save();
    }
}
