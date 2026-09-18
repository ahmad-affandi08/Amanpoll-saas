<?php

declare(strict_types=1);

namespace App\Domain\Aset\Domain\Repositories;

use App\Domain\Aset\Infrastructure\Persistence\Models\GaransiAset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface GaransiAsetRepository
{
    public function temukan(string $id): ?GaransiAset;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(GaransiAset $model): GaransiAset;
    public function hapus(GaransiAset $model): void;
}
