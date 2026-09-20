<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Domain\Repositories;

use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\RencanaPemeliharaan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface RencanaPemeliharaanRepository
{
    public function temukan(string $id): ?RencanaPemeliharaan;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(RencanaPemeliharaan $model): RencanaPemeliharaan;

    public function hapus(RencanaPemeliharaan $model): void;
}
