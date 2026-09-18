<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Domain\Repositories;

use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\WaktuHentiAset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface WaktuHentiAsetRepository
{
    public function temukan(string $id): ?WaktuHentiAset;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(WaktuHentiAset $model): WaktuHentiAset;
    public function hapus(WaktuHentiAset $model): void;
}
