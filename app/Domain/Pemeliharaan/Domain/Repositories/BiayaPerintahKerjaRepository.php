<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Domain\Repositories;

use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\BiayaPerintahKerja;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface BiayaPerintahKerjaRepository
{
    public function temukan(string $id): ?BiayaPerintahKerja;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(BiayaPerintahKerja $model): BiayaPerintahKerja;
    public function hapus(BiayaPerintahKerja $model): void;
}
