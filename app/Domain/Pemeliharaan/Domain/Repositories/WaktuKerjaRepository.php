<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Domain\Repositories;

use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\WaktuKerja;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface WaktuKerjaRepository
{
    public function temukan(string $id): ?WaktuKerja;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(WaktuKerja $model): WaktuKerja;

    public function hapus(WaktuKerja $model): void;
}
