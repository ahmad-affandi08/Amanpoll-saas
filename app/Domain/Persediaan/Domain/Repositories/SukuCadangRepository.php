<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Domain\Repositories;

use App\Domain\Persediaan\Infrastructure\Persistence\Models\SukuCadang;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface SukuCadangRepository
{
    public function temukan(string $id): ?SukuCadang;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(SukuCadang $model): SukuCadang;
    public function hapus(SukuCadang $model): void;
}
