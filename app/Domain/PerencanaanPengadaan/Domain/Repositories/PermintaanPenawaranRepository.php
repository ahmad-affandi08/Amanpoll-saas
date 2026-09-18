<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Domain\Repositories;

use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PermintaanPenawaran;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PermintaanPenawaranRepository
{
    public function temukan(string $id): ?PermintaanPenawaran;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(PermintaanPenawaran $model): PermintaanPenawaran;
    public function hapus(PermintaanPenawaran $model): void;
}
