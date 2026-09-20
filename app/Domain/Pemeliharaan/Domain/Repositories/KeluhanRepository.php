<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Domain\Repositories;

use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface KeluhanRepository
{
    public function temukan(string $id): ?Keluhan;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(Keluhan $model): Keluhan;

    public function hapus(Keluhan $model): void;
}
