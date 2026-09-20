<?php

declare(strict_types=1);

namespace App\Domain\Platform\Domain\Repositories;

use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface IzinRepository
{
    public function temukan(string $id): ?Izin;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(Izin $model): Izin;

    public function hapus(Izin $model): void;
}
