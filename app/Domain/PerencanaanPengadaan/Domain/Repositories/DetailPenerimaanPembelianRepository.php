<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Domain\Repositories;

use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\DetailPenerimaanPembelian;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface DetailPenerimaanPembelianRepository
{
    public function temukan(string $id): ?DetailPenerimaanPembelian;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(DetailPenerimaanPembelian $model): DetailPenerimaanPembelian;

    public function hapus(DetailPenerimaanPembelian $model): void;
}
