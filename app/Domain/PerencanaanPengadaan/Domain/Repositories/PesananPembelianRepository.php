<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Domain\Repositories;

use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PesananPembelian;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PesananPembelianRepository
{
    public function temukan(string $id): ?PesananPembelian;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(PesananPembelian $model): PesananPembelian;

    public function hapus(PesananPembelian $model): void;
}
