<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Domain\Repositories;

use App\Domain\Persediaan\Infrastructure\Persistence\Models\StokSukuCadang;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface StokSukuCadangRepository
{
    public function temukan(string $id): ?StokSukuCadang;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(StokSukuCadang $model): StokSukuCadang;

    public function hapus(StokSukuCadang $model): void;
}
