<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Domain\Repositories;

use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PenugasanPerintahKerja;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PenugasanPerintahKerjaRepository
{
    public function temukan(string $id): ?PenugasanPerintahKerja;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(PenugasanPerintahKerja $model): PenugasanPerintahKerja;

    public function hapus(PenugasanPerintahKerja $model): void;
}
