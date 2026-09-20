<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Domain\Repositories;

use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerjaAset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PerintahKerjaAsetRepository
{
    public function temukan(string $id): ?PerintahKerjaAset;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(PerintahKerjaAset $model): PerintahKerjaAset;

    public function hapus(PerintahKerjaAset $model): void;
}
