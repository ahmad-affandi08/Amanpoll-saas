<?php

declare(strict_types=1);

namespace App\Domain\Penyedia\Application\Actions;

use App\Domain\Penyedia\Infrastructure\Persistence\Models\KontakPenyedia;

final class HapusKontakPenyedia
{
    public function jalankan(KontakPenyedia $kontakPenyedia): void
    {
        $kontakPenyedia->delete();
    }
}
