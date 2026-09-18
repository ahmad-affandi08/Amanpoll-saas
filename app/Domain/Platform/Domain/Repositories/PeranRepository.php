<?php

declare(strict_types=1);

namespace App\Domain\Platform\Domain\Repositories;

use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PeranRepository
{
    public function temukan(string $id): ?Peran;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(Peran $model): Peran;
    public function hapus(Peran $model): void;
}
