<?php

declare(strict_types=1);

namespace App\Domain\Platform\Domain\Repositories;

use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PenggunaRepository
{
    public function temukan(string $id): ?Pengguna;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(Pengguna $model): Pengguna;

    public function hapus(Pengguna $model): void;
}
