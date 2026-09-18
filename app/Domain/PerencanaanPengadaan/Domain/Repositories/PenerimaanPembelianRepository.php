<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Domain\Repositories;

use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PenerimaanPembelian;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PenerimaanPembelianRepository
{
    public function temukan(string $id): ?PenerimaanPembelian;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(PenerimaanPembelian $model): PenerimaanPembelian;
    public function hapus(PenerimaanPembelian $model): void;
}
