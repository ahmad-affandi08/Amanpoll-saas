<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Domain\Repositories;

use App\Domain\Pelaporan\Infrastructure\Persistence\Models\KomponenDasbor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface KomponenDasborRepository
{
    public function temukan(string $id): ?KomponenDasbor;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(KomponenDasbor $model): KomponenDasbor;
    public function hapus(KomponenDasbor $model): void;
}
