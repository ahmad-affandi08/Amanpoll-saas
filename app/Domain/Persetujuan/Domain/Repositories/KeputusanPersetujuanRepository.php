<?php

declare(strict_types=1);

namespace App\Domain\Persetujuan\Domain\Repositories;

use App\Domain\Persetujuan\Infrastructure\Persistence\Models\KeputusanPersetujuan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface KeputusanPersetujuanRepository
{
    public function temukan(string $id): ?KeputusanPersetujuan;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(KeputusanPersetujuan $model): KeputusanPersetujuan;

    public function hapus(KeputusanPersetujuan $model): void;
}
