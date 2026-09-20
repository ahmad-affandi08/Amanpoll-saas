<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Domain\Repositories;

use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\TransaksiAnggaran;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TransaksiAnggaranRepository
{
    public function temukan(string $id): ?TransaksiAnggaran;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(TransaksiAnggaran $model): TransaksiAnggaran;

    public function hapus(TransaksiAnggaran $model): void;
}
