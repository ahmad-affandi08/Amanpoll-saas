<?php

declare(strict_types=1);

namespace App\Domain\Persetujuan\Domain\Repositories;

use App\Domain\Persetujuan\Infrastructure\Persistence\Models\TahapPersetujuan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TahapPersetujuanRepository
{
    public function temukan(string $id): ?TahapPersetujuan;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(TahapPersetujuan $model): TahapPersetujuan;
    public function hapus(TahapPersetujuan $model): void;
}
