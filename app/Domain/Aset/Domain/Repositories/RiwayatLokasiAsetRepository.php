<?php

declare(strict_types=1);

namespace App\Domain\Aset\Domain\Repositories;

use App\Domain\Aset\Infrastructure\Persistence\Models\RiwayatLokasiAset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface RiwayatLokasiAsetRepository
{
    public function temukan(string $id): ?RiwayatLokasiAset;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(RiwayatLokasiAset $model): RiwayatLokasiAset;

    public function hapus(RiwayatLokasiAset $model): void;
}
