<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Domain\Repositories;

use App\Domain\Persediaan\Infrastructure\Persistence\Models\Gudang;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface GudangRepository
{
    public function temukan(string $id): ?Gudang;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(Gudang $model): Gudang;

    public function hapus(Gudang $model): void;
}
