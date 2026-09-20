<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Domain\Repositories;

use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\Anggaran;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AnggaranRepository
{
    public function temukan(string $id): ?Anggaran;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(Anggaran $model): Anggaran;

    public function hapus(Anggaran $model): void;
}
