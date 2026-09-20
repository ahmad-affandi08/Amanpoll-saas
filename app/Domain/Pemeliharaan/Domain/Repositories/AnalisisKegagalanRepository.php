<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Domain\Repositories;

use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\AnalisisKegagalan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AnalisisKegagalanRepository
{
    public function temukan(string $id): ?AnalisisKegagalan;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(AnalisisKegagalan $model): AnalisisKegagalan;

    public function hapus(AnalisisKegagalan $model): void;
}
