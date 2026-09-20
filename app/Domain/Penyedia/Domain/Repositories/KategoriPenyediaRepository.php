<?php

declare(strict_types=1);

namespace App\Domain\Penyedia\Domain\Repositories;

use App\Domain\Penyedia\Infrastructure\Persistence\Models\KategoriPenyedia;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface KategoriPenyediaRepository
{
    public function temukan(string $id): ?KategoriPenyedia;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(KategoriPenyedia $model): KategoriPenyedia;

    public function hapus(KategoriPenyedia $model): void;
}
