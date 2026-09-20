<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Domain\Repositories;

use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\EntitasTag;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface EntitasTagRepository
{
    public function temukan(string $id): ?EntitasTag;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(EntitasTag $model): EntitasTag;

    public function hapus(EntitasTag $model): void;
}
