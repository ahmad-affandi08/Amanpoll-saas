<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Domain\Repositories;

use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\StandarKepatuhan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface StandarKepatuhanRepository
{
    public function temukan(string $id): ?StandarKepatuhan;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(StandarKepatuhan $model): StandarKepatuhan;
    public function hapus(StandarKepatuhan $model): void;
}
