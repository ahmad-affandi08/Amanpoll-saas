<?php

declare(strict_types=1);

namespace App\Domain\Kontrak\Domain\Repositories;

use App\Domain\Kontrak\Infrastructure\Persistence\Models\KontrakAset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface KontrakAsetRepository
{
    public function temukan(string $id): ?KontrakAset;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(KontrakAset $model): KontrakAset;

    public function hapus(KontrakAset $model): void;
}
