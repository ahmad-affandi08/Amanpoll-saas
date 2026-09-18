<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Domain\Repositories;

use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PenilaianUsulanAset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PenilaianUsulanAsetRepository
{
    public function temukan(string $id): ?PenilaianUsulanAset;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(PenilaianUsulanAset $model): PenilaianUsulanAset;
    public function hapus(PenilaianUsulanAset $model): void;
}
