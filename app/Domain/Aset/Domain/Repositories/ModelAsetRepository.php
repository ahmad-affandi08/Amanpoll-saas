<?php

declare(strict_types=1);

namespace App\Domain\Aset\Domain\Repositories;

use App\Domain\Aset\Infrastructure\Persistence\Models\ModelAset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ModelAsetRepository
{
    public function temukan(string $id): ?ModelAset;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(ModelAset $model): ModelAset;

    public function hapus(ModelAset $model): void;
}
