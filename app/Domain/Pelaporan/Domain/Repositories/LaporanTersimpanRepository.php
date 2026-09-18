<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Domain\Repositories;

use App\Domain\Pelaporan\Infrastructure\Persistence\Models\LaporanTersimpan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface LaporanTersimpanRepository
{
    public function temukan(string $id): ?LaporanTersimpan;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(LaporanTersimpan $model): LaporanTersimpan;
    public function hapus(LaporanTersimpan $model): void;
}
