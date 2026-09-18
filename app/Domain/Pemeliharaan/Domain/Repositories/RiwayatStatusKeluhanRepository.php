<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Domain\Repositories;

use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\RiwayatStatusKeluhan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface RiwayatStatusKeluhanRepository
{
    public function temukan(string $id): ?RiwayatStatusKeluhan;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(RiwayatStatusKeluhan $model): RiwayatStatusKeluhan;
    public function hapus(RiwayatStatusKeluhan $model): void;
}
