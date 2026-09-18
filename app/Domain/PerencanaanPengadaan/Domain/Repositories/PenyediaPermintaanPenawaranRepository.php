<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Domain\Repositories;

use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PenyediaPermintaanPenawaran;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PenyediaPermintaanPenawaranRepository
{
    public function temukan(string $id): ?PenyediaPermintaanPenawaran;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(PenyediaPermintaanPenawaran $model): PenyediaPermintaanPenawaran;
    public function hapus(PenyediaPermintaanPenawaran $model): void;
}
