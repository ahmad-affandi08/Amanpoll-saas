<?php

declare(strict_types=1);

namespace App\Domain\Aset\Domain\Repositories;

use App\Domain\Aset\Infrastructure\Persistence\Models\MeterAset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface MeterAsetRepository
{
    public function temukan(string $id): ?MeterAset;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(MeterAset $model): MeterAset;
    public function hapus(MeterAset $model): void;
}
