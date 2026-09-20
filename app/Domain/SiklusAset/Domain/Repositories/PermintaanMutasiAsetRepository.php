<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Domain\Repositories;

use App\Domain\SiklusAset\Infrastructure\Persistence\Models\PermintaanMutasiAset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PermintaanMutasiAsetRepository
{
    public function temukan(string $id): ?PermintaanMutasiAset;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(PermintaanMutasiAset $model): PermintaanMutasiAset;

    public function hapus(PermintaanMutasiAset $model): void;
}
