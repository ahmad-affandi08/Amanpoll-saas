<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Domain\Repositories;

use App\Domain\SiklusAset\Infrastructure\Persistence\Models\DetailPenghapusanAset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface DetailPenghapusanAsetRepository
{
    public function temukan(string $id): ?DetailPenghapusanAset;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(DetailPenghapusanAset $model): DetailPenghapusanAset;

    public function hapus(DetailPenghapusanAset $model): void;
}
