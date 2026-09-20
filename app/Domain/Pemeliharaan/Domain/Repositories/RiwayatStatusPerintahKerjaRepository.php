<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Domain\Repositories;

use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\RiwayatStatusPerintahKerja;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface RiwayatStatusPerintahKerjaRepository
{
    public function temukan(string $id): ?RiwayatStatusPerintahKerja;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(RiwayatStatusPerintahKerja $model): RiwayatStatusPerintahKerja;

    public function hapus(RiwayatStatusPerintahKerja $model): void;
}
