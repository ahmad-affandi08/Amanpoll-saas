<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Domain\Repositories;

use App\Domain\Persediaan\Infrastructure\Persistence\Models\KategoriSukuCadang;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface KategoriSukuCadangRepository
{
    public function temukan(string $id): ?KategoriSukuCadang;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(KategoriSukuCadang $model): KategoriSukuCadang;
    public function hapus(KategoriSukuCadang $model): void;
}
