<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Domain\Repositories;

use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PerintahKerjaRepository
{
    public function temukan(string $id): ?PerintahKerja;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(PerintahKerja $model): PerintahKerja;

    public function hapus(PerintahKerja $model): void;
}
