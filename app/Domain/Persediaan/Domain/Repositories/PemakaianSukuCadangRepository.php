<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Domain\Repositories;

use App\Domain\Persediaan\Infrastructure\Persistence\Models\PemakaianSukuCadang;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PemakaianSukuCadangRepository
{
    public function temukan(string $id): ?PemakaianSukuCadang;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(PemakaianSukuCadang $model): PemakaianSukuCadang;
    public function hapus(PemakaianSukuCadang $model): void;
}
