<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Domain\Repositories;

use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\DetailPermintaanPembelian;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface DetailPermintaanPembelianRepository
{
    public function temukan(string $id): ?DetailPermintaanPembelian;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(DetailPermintaanPembelian $model): DetailPermintaanPembelian;

    public function hapus(DetailPermintaanPembelian $model): void;
}
