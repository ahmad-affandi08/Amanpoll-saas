<?php

declare(strict_types=1);

namespace App\Domain\Aset\Domain\Repositories;

use App\Domain\Aset\Infrastructure\Persistence\Models\RelasiAset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface RelasiAsetRepository
{
    public function temukan(string $id): ?RelasiAset;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(RelasiAset $model): RelasiAset;

    public function hapus(RelasiAset $model): void;
}
