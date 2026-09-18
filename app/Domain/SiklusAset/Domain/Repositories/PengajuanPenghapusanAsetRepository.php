<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Domain\Repositories;

use App\Domain\SiklusAset\Infrastructure\Persistence\Models\PengajuanPenghapusanAset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PengajuanPenghapusanAsetRepository
{
    public function temukan(string $id): ?PengajuanPenghapusanAset;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(PengajuanPenghapusanAset $model): PengajuanPenghapusanAset;
    public function hapus(PengajuanPenghapusanAset $model): void;
}
