<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Domain\Repositories;

use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\UsulanAset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UsulanAsetRepository
{
    public function temukan(string $id): ?UsulanAset;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(UsulanAset $model): UsulanAset;

    public function hapus(UsulanAset $model): void;
}
