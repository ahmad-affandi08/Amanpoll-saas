<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Domain\Repositories;

use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PosAnggaran;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PosAnggaranRepository
{
    public function temukan(string $id): ?PosAnggaran;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(PosAnggaran $model): PosAnggaran;

    public function hapus(PosAnggaran $model): void;
}
