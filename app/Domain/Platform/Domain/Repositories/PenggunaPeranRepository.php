<?php

declare(strict_types=1);

namespace App\Domain\Platform\Domain\Repositories;

use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PenggunaPeranRepository
{
    public function temukan(string $id): ?PenggunaPeran;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(PenggunaPeran $model): PenggunaPeran;

    public function hapus(PenggunaPeran $model): void;
}
