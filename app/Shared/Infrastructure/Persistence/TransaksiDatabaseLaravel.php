<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence;

use App\Shared\Domain\Contracts\TransaksiDatabase;
use Illuminate\Support\Facades\DB;

final class TransaksiDatabaseLaravel implements TransaksiDatabase
{
    public function jalankan(callable $callback): mixed
    {
        return DB::transaction($callback, attempts: 3);
    }
}
