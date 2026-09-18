<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Domain\Repositories;

use App\Domain\SiklusAset\Infrastructure\Persistence\Models\DetailSerahTerimaAset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface DetailSerahTerimaAsetRepository
{
    public function temukan(string $id): ?DetailSerahTerimaAset;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(DetailSerahTerimaAset $model): DetailSerahTerimaAset;
    public function hapus(DetailSerahTerimaAset $model): void;
}
