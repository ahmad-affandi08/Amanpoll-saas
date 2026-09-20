<?php

declare(strict_types=1);

namespace App\Domain\Aset\Domain\Repositories;

use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface KategoriAsetRepository
{
    public function temukan(string $id): ?KategoriAset;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(KategoriAset $model): KategoriAset;

    public function hapus(KategoriAset $model): void;
}
