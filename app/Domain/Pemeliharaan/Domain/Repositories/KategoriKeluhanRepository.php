<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Domain\Repositories;

use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KategoriKeluhan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface KategoriKeluhanRepository
{
    public function temukan(string $id): ?KategoriKeluhan;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(KategoriKeluhan $model): KategoriKeluhan;
    public function hapus(KategoriKeluhan $model): void;
}
