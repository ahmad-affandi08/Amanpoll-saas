<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Domain\Repositories;

use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\SinkronisasiEksternal;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface SinkronisasiEksternalRepository
{
    public function temukan(string $id): ?SinkronisasiEksternal;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(SinkronisasiEksternal $model): SinkronisasiEksternal;

    public function hapus(SinkronisasiEksternal $model): void;
}
