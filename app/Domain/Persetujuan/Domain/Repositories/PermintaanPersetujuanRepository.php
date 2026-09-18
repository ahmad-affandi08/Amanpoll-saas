<?php

declare(strict_types=1);

namespace App\Domain\Persetujuan\Domain\Repositories;

use App\Domain\Persetujuan\Infrastructure\Persistence\Models\PermintaanPersetujuan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PermintaanPersetujuanRepository
{
    public function temukan(string $id): ?PermintaanPersetujuan;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(PermintaanPersetujuan $model): PermintaanPersetujuan;
    public function hapus(PermintaanPersetujuan $model): void;
}
