<?php

declare(strict_types=1);

namespace App\Domain\Aset\Domain\Repositories;

use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AsetRepository
{
    public function temukan(string $id): ?Aset;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(Aset $model): Aset;
    public function hapus(Aset $model): void;
}
