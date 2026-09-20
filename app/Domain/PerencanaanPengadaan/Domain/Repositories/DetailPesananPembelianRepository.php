<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Domain\Repositories;

use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\DetailPesananPembelian;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface DetailPesananPembelianRepository
{
    public function temukan(string $id): ?DetailPesananPembelian;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(DetailPesananPembelian $model): DetailPesananPembelian;

    public function hapus(DetailPesananPembelian $model): void;
}
