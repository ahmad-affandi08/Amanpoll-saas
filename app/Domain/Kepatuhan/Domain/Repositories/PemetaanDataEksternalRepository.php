<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Domain\Repositories;

use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\PemetaanDataEksternal;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PemetaanDataEksternalRepository
{
    public function temukan(string $id): ?PemetaanDataEksternal;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(PemetaanDataEksternal $model): PemetaanDataEksternal;

    public function hapus(PemetaanDataEksternal $model): void;
}
