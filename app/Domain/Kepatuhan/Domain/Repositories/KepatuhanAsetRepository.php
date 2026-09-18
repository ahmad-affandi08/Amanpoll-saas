<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Domain\Repositories;

use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\KepatuhanAset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface KepatuhanAsetRepository
{
    public function temukan(string $id): ?KepatuhanAset;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(KepatuhanAset $model): KepatuhanAset;
    public function hapus(KepatuhanAset $model): void;
}
