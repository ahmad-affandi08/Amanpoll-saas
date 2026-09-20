<?php

declare(strict_types=1);

namespace App\Domain\IntegrasiAudit\Domain\Repositories;

use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\KotakKeluarPeristiwa;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface KotakKeluarPeristiwaRepository
{
    public function temukan(string $id): ?KotakKeluarPeristiwa;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(KotakKeluarPeristiwa $model): KotakKeluarPeristiwa;

    public function hapus(KotakKeluarPeristiwa $model): void;
}
