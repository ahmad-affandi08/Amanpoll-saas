<?php

declare(strict_types=1);

namespace App\Domain\Platform\Domain\Repositories;

use App\Domain\Platform\Infrastructure\Persistence\Models\HariLibur;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface HariLiburRepository
{
    public function temukan(string $id): ?HariLibur;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(HariLibur $model): HariLibur;
    public function hapus(HariLibur $model): void;
}
