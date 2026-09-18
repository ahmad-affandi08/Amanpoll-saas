<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Domain\Repositories;

use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PermintaanPembelian;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PermintaanPembelianRepository
{
    public function temukan(string $id): ?PermintaanPembelian;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(PermintaanPembelian $model): PermintaanPembelian;
    public function hapus(PermintaanPembelian $model): void;
}
