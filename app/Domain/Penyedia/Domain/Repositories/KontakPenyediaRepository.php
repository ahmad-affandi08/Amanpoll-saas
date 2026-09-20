<?php

declare(strict_types=1);

namespace App\Domain\Penyedia\Domain\Repositories;

use App\Domain\Penyedia\Infrastructure\Persistence\Models\KontakPenyedia;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface KontakPenyediaRepository
{
    public function temukan(string $id): ?KontakPenyedia;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(KontakPenyedia $model): KontakPenyedia;

    public function hapus(KontakPenyedia $model): void;
}
