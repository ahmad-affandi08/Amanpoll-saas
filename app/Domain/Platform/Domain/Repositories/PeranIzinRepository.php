<?php

declare(strict_types=1);

namespace App\Domain\Platform\Domain\Repositories;

use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PeranIzinRepository
{
    public function temukan(string $id): ?PeranIzin;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(PeranIzin $model): PeranIzin;

    public function hapus(PeranIzin $model): void;
}
