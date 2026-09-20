<?php

declare(strict_types=1);

namespace App\Domain\Sinkronisasi\Domain\Repositories;

use App\Domain\Sinkronisasi\Infrastructure\Persistence\Models\AntrianSinkronisasi;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AntrianSinkronisasiRepository
{
    public function temukan(string $id): ?AntrianSinkronisasi;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(AntrianSinkronisasi $model): AntrianSinkronisasi;

    public function hapus(AntrianSinkronisasi $model): void;
}
