<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Domain\Repositories;

use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\DetailRencanaPengadaan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface DetailRencanaPengadaanRepository
{
    public function temukan(string $id): ?DetailRencanaPengadaan;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(DetailRencanaPengadaan $model): DetailRencanaPengadaan;
    public function hapus(DetailRencanaPengadaan $model): void;
}
