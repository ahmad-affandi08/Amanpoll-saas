<?php

declare(strict_types=1);

namespace App\Domain\Persetujuan\Domain\Repositories;

use App\Domain\Persetujuan\Infrastructure\Persistence\Models\AlurPersetujuan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AlurPersetujuanRepository
{
    public function temukan(string $id): ?AlurPersetujuan;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(AlurPersetujuan $model): AlurPersetujuan;

    public function hapus(AlurPersetujuan $model): void;
}
