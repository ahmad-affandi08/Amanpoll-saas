<?php

declare(strict_types=1);

namespace App\Domain\Sinkronisasi\Domain\Repositories;

use App\Domain\Sinkronisasi\Infrastructure\Persistence\Models\PenandaSinkronisasi;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PenandaSinkronisasiRepository
{
    public function temukan(string $id): ?PenandaSinkronisasi;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(PenandaSinkronisasi $model): PenandaSinkronisasi;
    public function hapus(PenandaSinkronisasi $model): void;
}
