<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Domain\Repositories;

use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\DetailPenawaranPenyedia;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface DetailPenawaranPenyediaRepository
{
    public function temukan(string $id): ?DetailPenawaranPenyedia;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(DetailPenawaranPenyedia $model): DetailPenawaranPenyedia;

    public function hapus(DetailPenawaranPenyedia $model): void;
}
