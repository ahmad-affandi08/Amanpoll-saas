<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Domain\Repositories;

use App\Domain\SiklusAset\Infrastructure\Persistence\Models\DetailMutasiAset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface DetailMutasiAsetRepository
{
    public function temukan(string $id): ?DetailMutasiAset;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(DetailMutasiAset $model): DetailMutasiAset;
    public function hapus(DetailMutasiAset $model): void;
}
