<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Domain\Repositories;

use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\RencanaPengadaan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface RencanaPengadaanRepository
{
    public function temukan(string $id): ?RencanaPengadaan;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(RencanaPengadaan $model): RencanaPengadaan;

    public function hapus(RencanaPengadaan $model): void;
}
