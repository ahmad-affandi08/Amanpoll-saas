<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Domain\Repositories;

use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KodeKegagalan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface KodeKegagalanRepository
{
    public function temukan(string $id): ?KodeKegagalan;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(KodeKegagalan $model): KodeKegagalan;

    public function hapus(KodeKegagalan $model): void;
}
